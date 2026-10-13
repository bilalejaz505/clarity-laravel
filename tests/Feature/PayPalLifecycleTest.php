<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pricing_identifies_missing_plan_ids_and_ready_packages(): void
    {
        $settings = app(AppSettingsService::class);
        $config = $settings->defaults();
        $config['paypalClientId'] = 'sandbox-client-id';
        $config['paypalWebhookId'] = 'sandbox-webhook-id';
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($config),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $settings->saveSecret('paypal', 'sandbox-secret');

        $this->get('/pricing')
            ->assertOk()
            ->assertSeeText('PayPal Plan ID Missing');

        foreach ($config['plans'] as $index => $plan) {
            $config['plans'][$index]['paypalPlanId'] = 'P-'.$plan['key'];
        }
        DB::table('app_settings')
            ->where('key', 'application')
            ->update(['data' => json_encode($config)]);

        $this->get('/pricing')
            ->assertOk()
            ->assertSeeText('Continue to PayPal')
            ->assertDontSeeText('PayPal Plan ID Missing');
    }

    public function test_administrator_can_verify_the_saved_paypal_connection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $settings = app(AppSettingsService::class);
        $config = $settings->defaults();
        $config['paypalClientId'] = 'sandbox-client-id';
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($config),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $settings->saveSecret('paypal', 'sandbox-secret');
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-token',
            ]),
            'https://api-m.sandbox.paypal.com/v1/catalogs/products*' => Http::response([
                'products' => [],
            ]),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/connection', ['provider' => 'paypal'])
            ->assertOk()
            ->assertJsonPath('environment', 'sandbox');
    }

    public function test_zero_price_package_activates_without_paypal(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $settings = app(AppSettingsService::class);
        $config = $settings->defaults();
        $config['plans'][0]['price'] = 0;
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($config),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson('/api/billing', [
                'action' => 'checkout',
                'planKey' => 'chat',
            ])
            ->assertOk()
            ->assertJson([
                'subscribed' => true,
                'redirectUrl' => url('/account'),
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_key' => 'chat',
            'status' => 'FREE_ACTIVE',
            'paid_until' => null,
        ]);
        $entitlement = app(EntitlementService::class)->entitlement(
            $user,
            false,
        );
        $this->assertTrue($entitlement['allowed']);
        $this->assertSame(['chat'], $entitlement['products']);
        $this->assertSame('Free package subscription', $entitlement['reason']);
        Http::assertNothingSent();
    }

    public function test_zero_price_package_cannot_be_activated_twice(): void
    {
        $user = User::factory()->create();
        $settings = app(AppSettingsService::class);
        $config = $settings->defaults();
        $config['plans'][0]['price'] = 0;
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($config),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = ['action' => 'checkout', 'planKey' => 'chat'];
        $this->actingAs($user)->postJson('/api/billing', $payload)->assertOk();
        $this->postJson('/api/billing', $payload)->assertConflict();
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_verified_payment_is_bound_to_the_owner_and_grants_only_the_paid_product(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $plan = $this->merchant();
        $this->subscription($user, $plan);
        $remote = $this->remote($user, $plan);
        $this->fakeProvider($remote);
        $this->actingAs($user)
            ->postJson('/api/billing', [
                'action' => 'refresh',
                'subscriptionId' => DB::table('subscriptions')
                    ->where('paypal_id', 'I-fixture')
                    ->value('id'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ACTIVE');
        $e = app(EntitlementService::class)->entitlement($user, false);
        $this->assertTrue($e['allowed']);
        $this->assertSame(['chat'], $e['products']);
        Http::assertSent(
            fn ($request) => str_ends_with(
                $request->url(),
                '/v1/billing/subscriptions/I-fixture',
            ),
        );
    }

    public function test_another_customer_cannot_inspect_or_cancel_a_subscription(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $plan = $this->merchant();
        $this->subscription($user, $plan);
        Http::preventStrayRequests();
        $this->actingAs($other)
            ->postJson('/api/billing', [
                'action' => 'cancel',
                'subscriptionId' => DB::table('subscriptions')
                    ->where('paypal_id', 'I-fixture')
                    ->value('id'),
            ])
            ->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_provider_ownership_mismatch_does_not_grant_access(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $plan = $this->merchant();
        $this->subscription($user, $plan);
        $remote = $this->remote($user, $plan);
        $remote['custom_id'] = 'another-account';
        $this->fakeProvider($remote);
        $this->actingAs($user)
            ->postJson('/api/billing', [
                'action' => 'refresh',
                'subscriptionId' => DB::table('subscriptions')
                    ->where('paypal_id', 'I-fixture')
                    ->value('id'),
            ])
            ->assertStatus(422);
        $this->assertFalse(
            app(EntitlementService::class)->entitlement($user, false)[
                'allowed'
            ],
        );
    }

    public function test_refund_blocks_paid_access_and_late_activation_cannot_regrant_old_payment(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $plan = $this->merchant();
        $this->subscription($user, $plan);
        $this->fakeProvider($this->remote($user, $plan));
        $event = [
            'id' => 'refund-fixture',
            'event_type' => 'PAYMENT.SALE.REFUNDED',
            'create_time' => now()->toISOString(),
            'resource' => ['billing_agreement_id' => 'I-fixture'],
        ];
        $this->withHeaders($this->signature())
            ->postJson('/api/paypal/webhook', $event)
            ->assertOk();
        $this->assertDatabaseHas('subscriptions', [
            'paypal_id' => 'I-fixture',
            'status' => 'REVIEW_REQUIRED',
            'paid_until' => null,
        ]);
        $event['id'] = 'late-activation';
        $event['event_type'] = 'BILLING.SUBSCRIPTION.ACTIVATED';
        $event['resource'] = ['paypal_id' => 'I-fixture'];
        $this->postJson('/api/paypal/webhook', $event)->assertOk();
        $this->assertFalse(
            app(EntitlementService::class)->entitlement($user, false)[
                'allowed'
            ],
        );
        $this->postJson('/api/paypal/webhook', $event)->assertJsonPath(
            'duplicate',
            true,
        );
        $this->assertDatabaseCount('billing_events', 2);
    }

    public function test_invalid_signature_creates_no_payment_event(): void
    {
        $plan = $this->merchant();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(
                ['access_token' => 'fake-token'],
            ),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(
                ['verification_status' => 'FAILURE'],
            ),
        ]);
        $this->withHeaders($this->signature())
            ->postJson('/api/paypal/webhook', [
                'id' => 'forged-event',
                'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            ])
            ->assertUnauthorized();
        $this->assertDatabaseCount('billing_events', 0);
    }

    private function merchant(): array
    {
        $settings = app(AppSettingsService::class);
        $s = $settings->defaults();
        $s['paypalClientId'] = 'fake-client';
        $s['paypalWebhookId'] = 'fake-webhook';
        $s['plans'][0]['paypalPlanId'] = 'P-fixture';
        DB::table('app_settings')->insert([
            'key' => 'application',
            'data' => json_encode($s),
            'revision' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $settings->saveSecret('paypal', 'fake-merchant-secret');

        return $s['plans'][0];
    }

    private function subscription(User $user, array $plan): void
    {
        DB::table('subscriptions')->insert([
            'paypal_id' => 'I-fixture',
            'user_id' => $user->id,
            'plan_key' => 'chat',
            'status' => 'APPROVAL_PENDING',
            'plan_snapshot' => json_encode($plan),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function remote(User $user, array $plan): array
    {
        return [
            'status' => 'ACTIVE',
            'plan_id' => $plan['paypalPlanId'],
            'custom_id' => (string) $user->id,
            'billing_info' => [
                'last_payment' => [
                    'time' => now()->subDay()->toISOString(),
                    'amount' => ['value' => '9.00', 'currency_code' => 'USD'],
                ],
                'next_billing_time' => now()->addDays(28)->toISOString(),
            ],
        ];
    }

    private function fakeProvider(array $remote): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(
                ['access_token' => 'fake-token'],
            ),
            'https://api-m.sandbox.paypal.com/v1/billing/subscriptions/I-fixture' => Http::response(
                $remote,
            ),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(
                ['verification_status' => 'SUCCESS'],
            ),
        ]);
    }

    private function signature(): array
    {
        return [
            'paypal-transmission-id' => 'fixture',
            'paypal-transmission-time' => now()->toISOString(),
            'paypal-cert-url' => 'https://api-m.sandbox.paypal.com/fixture-cert',
            'paypal-auth-algo' => 'SHA256withRSA',
            'paypal-transmission-sig' => 'fixture-signature',
        ];
    }
}
