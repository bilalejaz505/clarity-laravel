<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AppSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacy extends Command
{
    protected $signature = 'clarity:import-legacy {path : Original project directory} {--dry-run}';

    protected $description = 'Import the original D1 control data into an empty Laravel database; never changes the original files.';

    public function handle(AppSettingsService $settings): int
    {
        $root = realpath($this->argument('path'));
        if (! $root || ! is_dir($root.DIRECTORY_SEPARATOR.'.wrangler')) {
            $this->error('Original local D1 state was not found.');

            return self::FAILURE;
        }

        $sqlite = null;
        $file = null;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.DIRECTORY_SEPARATOR.'.wrangler', \FilesystemIterator::SKIP_DOTS)) as $candidate) {
            if ($candidate->getExtension() !== 'sqlite') {
                continue;
            }try {
                $db = new \SQLite3($candidate->getPathname(), SQLITE3_OPEN_READONLY);
                if ($db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='control_config'")) {
                    $sqlite = $db;
                    $file = $candidate->getPathname();
                    break;
                }
                $db->close();
            } catch (\Throwable) {
            }
        }

        if (! $sqlite) {
            $this->error('No Clarity control database found.');

            return self::FAILURE;
        }

        $tables = ['control_config', 'control_secrets', 'control_users', 'control_subscriptions', 'control_usage', 'control_counters', 'control_audit', 'control_events'];
        $rows = [];
        $summary = [];
        foreach ($tables as $table) {
            $rows[$table] = [];
            $result = $sqlite->query("SELECT * FROM $table");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[$table][] = $row;
            }
            $summary[$table] = count($rows[$table]);
        }
        $sqlite->close();
        $this->table(['Table', 'Rows'], array_map(fn ($name, $count) => [$name, $count], array_keys($summary), array_values($summary)));
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $fingerprint = hash_file('sha256', $file);
        if (DB::table('legacy_imports')->where('fingerprint', $fingerprint)->exists()) {
            $this->info('This snapshot has already been imported.');

            return self::SUCCESS;
        }

        if (DB::table('app_settings')->exists() || DB::table('subscriptions')->exists() || User::exists()) {
            $this->error('Import requires an empty application database to avoid overwriting existing accounts or settings.');

            return self::FAILURE;
        }

        $date = fn ($value) => $value ? CarbonImmutable::parse($value) : null;
        $users = [];
        $counters = [];
        try {
            DB::transaction(function () use ($rows, $root, $settings, $date, &$users, &$counters, $fingerprint, $summary) {
                foreach ($rows['control_users'] as $row) {
                    $user = User::create(['name' => $row['name'], 'email' => strtolower($row['email']), 'password' => Str::random(64)]);
                    $user->forceFill(['legacy_id' => $row['id'], 'status' => $row['status'], 'email_verified_at' => null, 'trial_start' => $date($row['trial_start']), 'trial_end' => $date($row['trial_end']), 'last_seen' => $date($row['last_seen']), 'created_at' => $date($row['created_at'])])->save();
                    $users[$row['id']] = $user->id;
                }

                foreach ($rows['control_config'] as $row) {
                    DB::table('app_settings')->insert(['key' => $row['id'], 'data' => $row['data'], 'revision' => $row['revision'], 'created_at' => $date($row['updated_at']), 'updated_at' => $date($row['updated_at'])]);
                }

                if ($rows['control_secrets']) {
                    $envPath = $root.DIRECTORY_SEPARATOR.'.dev.vars';
                    $env = is_file($envPath) ? file_get_contents($envPath) : '';
                    preg_match('/^SETTINGS_ENCRYPTION_KEY=(.*)$/m', $env, $match);
                    $key = base64_decode(trim($match[1] ?? '', " \r\n\"'"), true);
                    if (! $key || strlen($key) !== 32) {
                        throw new \RuntimeException('Original encryption key unavailable. Retain the original settings file.');
                    }
                    foreach ($rows['control_secrets'] as $row) {
                        [$iv,$body] = explode('.', $row['ciphertext'], 2);
                        $raw = base64_decode($body, true);
                        $plain = openssl_decrypt(substr($raw, 0, -16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($iv, true), substr($raw, -16), $row['id']);
                        if ($plain === false) {
                            throw new \RuntimeException('An original credential could not be decrypted.');
                        }
                        $settings->saveSecret($row['id'], $plain);
                    }
                }

                foreach ($rows['control_subscriptions'] as $row) {
                    if (! isset($users[$row['user_id']])) {
                        continue;
                    }
                    DB::table('subscriptions')->insert(['paypal_id' => $row['id'], 'user_id' => $users[$row['user_id']], 'provider_custom_id' => $row['user_id'], 'plan_key' => $row['plan_key'], 'status' => $row['status'], 'period_start' => $date($row['period_start']), 'paid_until' => $date($row['paid_until']), 'provider_updated_at' => $date($row['provider_updated_at'] ?? null), 'refund_cutoff' => $date($row['refund_cutoff'] ?? null), 'plan_snapshot' => $row['plan_snapshot'] ?? null, 'created_at' => $date($row['updated_at']), 'updated_at' => $date($row['updated_at'])]);
                }

                foreach ($rows['control_counters'] as $row) {
                    foreach ($users as $legacy => $userId) {
                        if (str_starts_with($row['id'], $legacy.':')) {
                            $id = hash('sha256', $userId.':'.substr($row['id'], strlen($legacy) + 1));
                            $counters[$row['id']] = DB::table('usage_counters')->insertGetId(['bucket_key' => $id, 'user_id' => $userId, 'messages' => $row['messages'], 'requests' => $row['requests'], 'created_at' => now(), 'updated_at' => now()]);
                            break;
                        }
                    }
                }

                foreach ($rows['control_usage'] as $row) {
                    if (! isset($users[$row['user_id']],$counters[$row['bucket']])) {
                        continue;
                    }
                    DB::table('usage_reservations')->insert(['user_id' => $users[$row['user_id']], 'counter_id' => $counters[$row['bucket']], 'product' => $row['product'], 'kind' => $row['kind'], 'messages' => $row['messages'], 'status' => $row['status'], 'created_at' => $date($row['created_at']), 'updated_at' => $date($row['created_at'])]);
                }

                foreach ($rows['control_audit'] as $row) {
                    DB::table('audit_logs')->insert(['actor' => (string) ($users[$row['actor']] ?? $row['actor']), 'action' => $row['action'], 'detail' => $row['detail'], 'created_at' => $date($row['created_at'])]);
                }

                foreach ($rows['control_events'] as $row) {
                    DB::table('billing_events')->insert(['paypal_event_id' => $row['id'], 'type' => $row['type'], 'provider_subscription_id' => $row['subscription_id'], 'status' => $row['status'], 'created_at' => $date($row['created_at']), 'updated_at' => $date($row['created_at'])]);
                }

                DB::table('legacy_imports')->insert(['fingerprint' => $fingerprint, 'summary' => json_encode($summary), 'created_at' => now()]);
            });
        } catch (\Throwable $e) {
            $this->error('Import rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Legacy data imported. Existing accounts need Laravel password reset and email verification; provider credentials were re-encrypted. Browser workspaces can be restored from their JSON backups.');

        return self::SUCCESS;
    }
}
