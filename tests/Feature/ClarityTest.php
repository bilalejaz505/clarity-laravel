<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use App\Services\PayPalService;
use App\Services\UsageMeter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClarityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_real_password_login(): void
    {
        $this->get('/')->assertOk()->assertDontSee('My account');
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('currently unavailable');
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->get('/')
            ->assertSeeText('Dashboard')
            ->assertDontSeeText('Log in');
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_registration_hashes_password_and_sends_verification(): void
    {
        Notification::fake();
        $this->post('/register', [
            'name' => 'New user',
            'email' => 'new@example.com',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
            'is_admin' => true,
        ])->assertRedirect('/account');
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->assertNotEquals('long-password-123', $user->password);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_password_reset_uses_laravel_broker(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification,
        ) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(
            Hash::check('new-secure-password', $user->fresh()->password),
        );
    }

    public function test_forged_headers_cannot_authenticate_or_access_admin(): void
    {
        $this->withHeaders([
            'oai-authenticated-user-email' => 'chatgpt@edesign.com.sa',
            'oai-authenticated-user-id' => 'forged',
        ])
            ->getJson('/api/admin')
            ->assertUnauthorized();
        $this->getJson('/api/auth')->assertJson(['authenticated' => false]);
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/admin')->assertForbidden();
    }

    public function test_authenticated_account_and_access_pages_use_the_workspace_shell(): void
    {
        $user = User::factory()->create();
        $bundle = collect(app(AppSettingsService::class)->defaults()['plans'])
            ->firstWhere('key', 'bundle');
        Subscription::factory()->for($user)->create([
            'plan_key' => 'bundle',
            'status' => 'ACTIVE',
            'paid_until' => now()->addMonth(),
            'plan_snapshot' => $bundle,
        ]);

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('id="member-sidebar"', false)
            ->assertSee('class="product-switch"', false)
            ->assertSeeText('Chat Insights')
            ->assertSeeText('Freelance Tracker')
            ->assertSeeText('Conversations')
            ->assertSeeText('Conversation insights')
            ->assertSeeText('Chat statistics')
            ->assertSee('/chats?view=overview', false)
            ->assertDontSeeText('Chat dashboard')
            ->assertDontSeeText('Contracts dashboard');

        $this->get('/account?product=freelance')
            ->assertOk()
            ->assertSeeText('Client conversations')
            ->assertSeeText('Contract tracker')
            ->assertSeeText('Tasks & milestones')
            ->assertSee('/contracts?view=overview', false);

        $this->get('/chats')
            ->assertOk()
            ->assertSee('data-product-nav="chat"', false)
            ->assertSee('data-product-nav="freelance"', false)
            ->assertSeeInOrder(
                [
                    'data-product-nav="freelance"',
                    'aria-label="Freelance Tracker"',
                    'hidden',
                ],
                false,
            );

        $this->get('/contracts')
            ->assertOk()
            ->assertSeeInOrder(
                [
                    'data-product-nav="chat"',
                    'aria-label="Chat Insights"',
                    'hidden',
                ],
                false,
            )
            ->assertSee('data-product-nav="freelance"', false);

        $unsubscribed = User::factory()->create();
        $this->actingAs($unsubscribed)
            ->get('/chats')
            ->assertForbidden()
            ->assertSee('id="member-sidebar"', false)
            ->assertSeeText('Choose your next step.');

        $unsubscribed->forceFill(['email_verified_at' => null])->save();
        $this->get('/email/verify')
            ->assertOk()
            ->assertSee('id="member-sidebar"', false)
            ->assertSeeText('Verify your email');
    }

    public function test_dashboard_entry_and_product_switch_follow_the_users_package(): void
    {
        $plans = collect(app(AppSettingsService::class)->defaults()['plans']);

        foreach (
            [
                'chat' => ['/chats', true, false],
                'freelance' => ['/contracts', false, true],
                'bundle' => ['/chats', true, true],
            ] as $planKey => [$destination, $showsChat, $showsContracts]
        ) {
            $user = User::factory()->create([
                'email' => "{$planKey}@example.com",
                'password' => 'correct-password',
            ]);
            Subscription::factory()->for($user)->create([
                'plan_key' => $planKey,
                'status' => 'ACTIVE',
                'paid_until' => now()->addMonth(),
                'plan_snapshot' => $plans->firstWhere('key', $planKey),
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertRedirect($destination);

            $this->get('/dashboard')->assertRedirect($destination);
            $workspace = $this->get($destination)->assertOk();
            $showsChat
                ? $workspace->assertSeeText('Chat Insights')
                : $workspace->assertDontSeeText('Chat Insights');
            $showsContracts
                ? $workspace->assertSeeText('Freelance Tracker')
                : $workspace->assertDontSeeText('Freelance Tracker');

            $account = $this->get('/account')->assertOk();
            $showsChat
                ? $account
                    ->assertSeeText('Conversations')
                    ->assertSeeText('Conversation insights')
                : $account
                    ->assertSeeText('Client conversations')
                    ->assertSeeText('Contract tracker');
            $showsContracts
                ? $account->assertSeeText('Freelance Tracker')
                : $account->assertDontSeeText('Freelance Tracker');

            $this->post('/logout');
        }
    }

    public function test_production_loopback_has_no_free_plan_and_unverified_accounts_are_denied(): void
    {
        $user = User::factory()->create();
        $access = app(EntitlementService::class);
        $this->assertFalse($access->local(Request::create('http://localhost')));
        $this->assertFalse($access->entitlement($user, false)['allowed']);
        $user->forceFill(['email_verified_at' => null])->save();
        $this->assertStringContainsString(
            'Verify your email',
            $access->entitlement($user, false)['reason'],
        );
        $this->app->instance('env', 'local');
        $this->assertTrue($access->local(Request::create('http://127.0.0.1')));
        $this->assertFalse(
            $access->local(Request::create('http://localhost.evil.invalid')),
        );
    }

    public function test_trial_starts_once_and_expires(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/account')->assertOk();
        $this->postJson('/api/account')->assertStatus(409);
        $this->getJson('/api/account')->assertJsonPath(
            'entitlement.plan',
            'Trial',
        );
        $this->travel(8)->days();
        $this->getJson('/api/account')->assertJsonPath(
            'entitlement.allowed',
            false,
        );
    }

    public function test_failed_ai_is_credited_and_client_key_headers_are_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/account')->assertOk();
        $this->withHeaders([
            'X-OpenAI-Key' => 'sk-fake-public-key-that-must-not-work',
        ])
            ->postJson('/api/ai', $this->aiBody())
            ->assertStatus(422);
        $this->getJson('/api/account')
            ->assertJsonPath('usage.messages', 0)
            ->assertJsonPath('usage.requests', 0);
    }

    public function test_ai_response_evidence_is_validated_before_charging(): void
    {
        $settings = app(AppSettingsService::class);
        $settings->saveSecret('openai', 'sk-test-server-key');
        $s = $settings->defaults();
        $s['aiModel'] = 'test-model';
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($s),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'status' => 'completed',
                'output' => [
                    [
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode([
                                    'answer' => 'Unsupported answer',
                                    'evidence' => ['invented-id'],
                                ]),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/account')->assertOk();
        $this->postJson('/api/ai', $this->aiBody())->assertStatus(422);
        $this->getJson('/api/account')->assertJsonPath('usage.requests', 0);
        Http::assertSent(
            fn ($r) => $r['store'] === false && $r['model'] === 'test-model',
        );
    }

    public function test_settings_secrets_and_revision_conflicts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $settings = app(AppSettingsService::class);
        $body = [
            'action' => 'settings',
            'revision' => 0,
            'config' => $settings->defaults(),
            'openaiKey' => 'sk-abcdefghijklmnopqrstuv',
        ];
        $this->actingAs($admin)->postJson('/api/admin', $body)->assertOk();
        $this->getJson('/api/admin')
            ->assertDontSee('sk-abcdefghijklmnopqrstuv')
            ->assertJsonPath('revision', 1);
        $this->assertStringNotContainsString(
            'sk-abcdefghijklmnopqrstuv',
            DB::table('app_secrets')->value('ciphertext'),
        );
        $this->assertSame(
            'sk-abcdefghijklmnopqrstuv',
            $settings->secret('openai'),
        );
        $this->postJson('/api/admin', $body)->assertStatus(409);
    }

    public function test_admin_can_configure_a_zero_price_package(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $settings = app(AppSettingsService::class);
        $config = $settings->defaults();
        $config['plans'][0]['price'] = 0;

        $this->actingAs($admin)
            ->postJson('/api/admin', [
                'action' => 'settings',
                'revision' => 0,
                'config' => $config,
            ])
            ->assertOk();

        $this->assertSame(0, $settings->get()['plans'][0]['price']);
    }

    public function test_suspension_and_admin_self_protection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($admin)
            ->postJson('/api/admin', [
                'action' => 'user-status',
                'userId' => $admin->id,
                'status' => 'suspended',
            ])
            ->assertStatus(422);
        $this->postJson('/api/admin', [
            'action' => 'user-status',
            'userId' => $user->id,
            'status' => 'suspended',
        ])->assertOk();
        $this->actingAs($user)->getJson('/api/workspace')->assertForbidden();
    }

    public function test_private_workspace_isolation_and_expired_account_export(): void
    {
        $a = User::factory()->create([
            'trial_start' => now(),
            'trial_end' => now()->addDay(),
        ]);
        $b = User::factory()->create();
        $store = ['conversations' => [], 'tasks' => [], 'invoices' => []];
        $this->actingAs($a)
            ->putJson('/api/workspace', [
                'store' => $store,
                'product' => 'chat',
            ])
            ->assertOk();
        $this->actingAs($b)
            ->getJson('/api/workspace')
            ->assertJsonPath('store', null);
        $this->actingAs($a)
            ->getJson('/api/workspace')
            ->assertJsonPath('store.tasks', []);
        $this->travel(2)->days();
        $this->putJson('/api/workspace', [
            'store' => $store,
        ])->assertForbidden();
        $this->getJson('/api/workspace')->assertOk();
        $this->deleteJson('/api/workspace')->assertOk();
    }

    public function test_paid_product_boundaries_and_cancelled_expiry(): void
    {
        $u = User::factory()->create();
        $plan = app(AppSettingsService::class)->defaults()['plans'][0];
        DB::table('subscriptions')->insert([
            'paypal_id' => 'I-test',
            'user_id' => $u->id,
            'plan_key' => 'chat',
            'status' => 'CANCELLED',
            'paid_until' => now()->addDay(),
            'plan_snapshot' => json_encode($plan),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $e = app(EntitlementService::class)->entitlement($u, false);
        $this->assertTrue($e['allowed']);
        $this->assertSame(['chat'], $e['products']);
        $this->travel(2)->days();
        $this->assertFalse(
            app(EntitlementService::class)->entitlement($u, false)['allowed'],
        );
    }

    public function test_paid_status_without_settled_payment_does_not_grant_access(): void
    {
        $paypal = app(PayPalService::class);
        $plan = app(AppSettingsService::class)->defaults()['plans'][0];
        $this->assertSame(
            [null, null],
            $paypal->paidWindow(['status' => 'ACTIVE'], $plan),
        );
        $remote = [
            'status' => 'ACTIVE',
            'billing_info' => [
                'next_billing_time' => now()->addDay()->toISOString(),
                'last_payment' => [
                    'time' => now()->subMonths(2)->toISOString(),
                    'amount' => ['value' => '9', 'currency_code' => 'USD'],
                ],
            ],
        ];
        $this->assertSame([null, null], $paypal->paidWindow($remote, $plan));
    }

    public function test_usage_reservations_are_bounded_and_failures_credit_once(): void
    {
        $user = User::factory()->create([
            'trial_start' => now(),
            'trial_end' => now()->addDay(),
        ]);
        $r = Request::create('http://localhost/api/ai');
        $r->setUserResolver(fn () => $user);
        $usage = app(UsageMeter::class);
        $reservation = $usage->reserve($r, 'chat', 'ask', 5);
        $usage->finish($reservation, false);
        $usage->finish($reservation, false);
        $this->assertSame(
            0,
            (int) DB::table('usage_counters')->value('requests'),
        );
        $this->assertSame(
            0,
            (int) DB::table('usage_counters')->value('messages'),
        );
    }

    public function test_unsigned_or_unconfigured_webhooks_never_grant_access(): void
    {
        $this->postJson('/api/paypal/webhook', [
            'id' => 'fixture',
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        ])->assertStatus(503);
        $this->assertDatabaseCount('billing_events', 0);
    }

    private function aiBody(): array
    {
        return [
            'mode' => 'ask',
            'purpose' => 'personal',
            'question' => 'What was agreed?',
            'messages' => [
                [
                    'id' => 'fixture-message',
                    'sender' => 'Fixture',
                    'text' => 'Hello',
                    'timestamp' => null,
                    'source' => 'Test fixture',
                ],
            ],
        ];
    }
}
