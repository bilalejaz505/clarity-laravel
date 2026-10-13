<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogService
{
    public function entries(): Collection
    {
        $entries = DB::table('audit_logs')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        $users = User::query()
            ->whereIn(
                'id',
                $entries
                    ->pluck('actor')
                    ->filter(fn ($actor): bool => ctype_digit((string) $actor)),
            )
            ->get(['id', 'name', 'email'])
            ->keyBy(fn (User $user): string => (string) $user->id);

        return $entries->map(function ($entry) use ($users): array {
            $actor = $users->get((string) $entry->actor);

            return [
                'created_at' => $entry->created_at,
                'actor_name' => $actor?->name ??
                    ($entry->actor === 'console'
                        ? 'System Console'
                        : Str::headline($entry->actor)),
                'actor_email' => $actor?->email ?? 'System process',
                'area' => $this->area($entry->action),
                'activity' => $this->activity($entry->action),
                'detail' => $entry->detail,
            ];
        });
    }

    public function settingsChanges(
        array $before,
        array $after,
        array $replacedSecrets,
    ): array {
        $areas = [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) === $value) {
                continue;
            }
            $area = match (true) {
                str_starts_with($key, 'ai') => 'openai',
                str_starts_with($key, 'paypal'), $key === 'plans' => 'paypal',
                str_starts_with($key, 'smtp') => 'email',
                default => 'site',
            };
            $areas[$area][] = $key === 'plans'
                ? 'Packages'
                : Str::headline($key);
        }
        foreach ($replacedSecrets as $secret) {
            [$area, $label] = match ($secret) {
                'openaiKey' => ['openai', 'OpenAI Key'],
                'paypalSecret' => ['paypal', 'PayPal Secret'],
                'smtpPassword' => ['email', 'SMTP Password'],
            };
            $areas[$area][] = $label;
        }

        return array_map(
            fn (array $fields): array => array_values(array_unique($fields)),
            $areas,
        );
    }

    private function activity(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'settings.') => 'Settings Updated',
            str_starts_with($action, 'secret.') => 'Credential Removed',
            $action === 'user.status' => 'Account Status Changed',
            $action === 'subscription.free_activated' => 'Free Package Activated',
            $action === 'subscription.reconciled' => 'Subscription Reconciled',
            $action === 'trial.started' => 'Trial Started',
            $action === 'local.initialized' => 'Local Data Initialized',
            default => Str::headline(str_replace('.', ' ', $action)),
        };
    }

    private function area(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'settings.openai'),
            str_starts_with($action, 'secret.openai') => 'OpenAI',
            str_starts_with($action, 'settings.paypal'),
            str_starts_with($action, 'secret.paypal'),
            str_starts_with($action, 'subscription.'),
            str_starts_with($action, 'billing.') => 'PayPal & Plans',
            str_starts_with($action, 'settings.email'),
            str_starts_with($action, 'secret.smtp') => 'Email & SMTP',
            str_starts_with($action, 'user.') => 'Users',
            str_starts_with($action, 'trial.') => 'Member Account',
            str_starts_with($action, 'local.') => 'Local Setup',
            str_starts_with($action, 'settings.site') => 'System & Site',
            default => 'System',
        };
    }
}
