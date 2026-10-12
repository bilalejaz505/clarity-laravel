<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AppSettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LocalWorkspaceSeeder extends Seeder
{
    public function __construct(private ?string $adminPassword = null, private ?string $userPassword = null) {}

    public function run(): void
    {
        if (! app()->environment('local') || ! $this->adminPassword || ! $this->userPassword) {
            throw new RuntimeException('Use clarity:reset-local-data for explicitly requested local setup.');
        }

        DB::transaction(function (): void {
            foreach (['workspaces', 'usage_reservations', 'usage_counters', 'billing_events', 'checkout_locks', 'subscriptions', 'sessions', 'password_reset_tokens', 'audit_logs', 'legacy_imports', 'users', 'cache_locks', 'cache'] as $table) {
                DB::table($table)->delete();
            }

            $settings = app(AppSettingsService::class);
            AppSetting::updateOrCreate(['key' => 'application'], ['data' => $settings->defaults(), 'revision' => $settings->revision() + 1]);
            $admin = User::create(['name' => 'Admin', 'email' => 'admin@clarity.com', 'password' => $this->adminPassword]);
            $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
            $user = User::create(['name' => 'User', 'email' => 'user@clarity.com', 'password' => $this->userPassword]);
            $user->forceFill(['email_verified_at' => now(), 'trial_start' => now(), 'trial_end' => now()->addDays(7)])->save();

            foreach ([$admin, $user] as $owner) {
                Workspace::create(['user_id' => $owner->id, 'data' => $this->workspace()]);
            }
            $settings->audit('console', 'local.initialized', 'Created owner and member accounts, three plans, and independent starter workspaces.');
        });
    }

    private function workspace(): array
    {
        $personalId = (string) Str::uuid();
        $projectId = (string) Str::uuid();
        $personalMessages = $this->messages([
            ['Sarah', 'How is your new role going?'], ['Nora', 'Really well! The team has been welcoming.'],
            ['Sarah', 'That is lovely to hear. Shall we catch up this weekend?'], ['Nora', 'Yes, Saturday afternoon works.'],
            ['Sarah', 'Let us meet at 3 pm. I will send you the location.'], ['Nora', 'Perfect, thanks for organizing.'],
        ]);
        $projectMessages = $this->messages([
            ['Morgan', 'We need a landing page and three service pages for Northstar Studio.'],
            ['Sarah', 'I can design and build those four pages for USD 1800 with two revision rounds.'],
            ['Morgan', 'Agreed on those four pages, USD 1800, and two revisions.'],
            ['Sarah', 'The deposit is USD 900 before work starts, with USD 900 after final approval.'],
            ['Morgan', 'Yes, that payment schedule works for us.'],
            ['Sarah', 'The first design delivery will be 2026-10-20, provided copy and images arrive by 2026-10-16.'],
            ['Morgan', 'Agreed. We will supply the copy and images by 2026-10-16.'],
            ['Morgan', 'Could we also add a booking system?'],
            ['Sarah', 'That is outside the agreed scope. I will prepare a separate estimate.'],
            ['Morgan', 'Understood. Please send the estimate before we decide.'],
        ]);
        $term = fn (string $category, string $title, string $detail, array $evidence, string $due = '', ?float $amount = null): array => [
            'id' => (string) Str::uuid(), 'category' => $category, 'title' => $title, 'detail' => $detail, 'status' => 'accepted',
            'evidence' => array_map(fn (int $index): string => $projectMessages[$index]['id'], $evidence), 'due' => $due,
            'amount' => $amount, 'currency' => $amount === null ? 'UNKNOWN' : 'USD', 'completed' => false, 'note' => '',
        ];

        return [
            'conversations' => [
                ['id' => $personalId, 'name' => 'Sarah & Nora', 'platform' => 'WhatsApp', 'purpose' => 'personal', 'messages' => $personalMessages, 'created' => now()->toISOString(), 'sample' => true, 'analysis' => null],
                ['id' => $projectId, 'name' => 'Northstar Studio · Website', 'platform' => 'WhatsApp', 'purpose' => 'freelance', 'messages' => $projectMessages, 'created' => now()->toISOString(), 'sample' => true, 'analysis' => [
                    'summary' => 'Four website pages agreed for USD 1800, two revision rounds, and a conditional first delivery on October 20. The booking system remains an unapproved change.',
                    'engine' => 'local', 'analyzedCount' => count($projectMessages), 'insights' => [], 'terms' => [
                        $term('scope', 'Four-page website', 'Landing page and three service pages, with two revision rounds.', [1, 2]),
                        $term('payment', 'Project fee and deposit', 'USD 1800 total: USD 900 deposit and USD 900 on approval.', [1, 2, 3, 4], '', 1800.0),
                        $term('deadline', 'First design delivery', 'Delivery on 2026-10-20 depends on assets arriving by 2026-10-16.', [5, 6], '2026-10-20'),
                        array_replace($term('change', 'Booking system request', 'Separate estimate required; approval is still pending.', [7, 8, 9]), ['status' => 'proposed']),
                    ],
                ]],
            ],
            'tasks' => [
                ['id' => (string) Str::uuid(), 'title' => 'Collect copy and brand assets', 'due' => '2026-10-16', 'status' => 'todo', 'conversationId' => $projectId, 'note' => 'Client to provide final copy and images.'],
                ['id' => (string) Str::uuid(), 'title' => 'Prepare first design presentation', 'due' => '2026-10-20', 'status' => 'doing', 'conversationId' => $projectId, 'note' => 'Four pages, matching the reviewed scope.'],
            ],
            'invoices' => [
                ['id' => (string) Str::uuid(), 'conversationId' => $projectId, 'client' => 'Northstar Studio', 'description' => 'Website project deposit', 'amount' => 900.0, 'currency' => 'USD', 'due' => '2026-10-16', 'status' => 'draft'],
                ['id' => (string) Str::uuid(), 'conversationId' => $projectId, 'client' => 'Northstar Studio', 'description' => 'Balance after final approval', 'amount' => 900.0, 'currency' => 'USD', 'due' => '', 'status' => 'draft'],
            ],
            'reviews' => [],
        ];
    }

    private function messages(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => ['id' => (string) Str::uuid(), 'sender' => $row[0], 'text' => $row[1], 'timestamp' => now()->startOfDay()->addHours(9)->addMinutes($index * 4)->toISOString(), 'source' => 'Starter workspace'], $rows, array_keys($rows));
    }
}
