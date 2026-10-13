<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Services\UsageMeter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class IntegerIdentifiersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accounts_receive_incrementing_ids_and_workspace_ownership_uses_user_id(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->assertIsInt($first->id);
        $this->assertSame($first->id + 1, $second->id);
        $store = [
            'conversations' => [],
            'tasks' => [],
            'invoices' => [],
            'reviews' => [],
        ];
        $workspace = Workspace::factory()->create([
            'user_id' => $second->id,
            'data' => $store,
        ]);
        $this->assertNotSame($workspace->id, $second->id);
        $this->actingAs($second)
            ->getJson('/api/workspace')
            ->assertJsonPath('store', $store);
        $this->actingAs($first)
            ->getJson('/api/workspace')
            ->assertJsonPath('store', null);
    }

    public function test_quota_rows_use_numeric_references_and_failed_requests_credit_once(): void
    {
        $this->freezeTime();
        $user = User::factory()->create([
            'trial_start' => now(),
            'trial_end' => now()->addDay(),
        ]);
        $request = Request::create('http://localhost/api/ai');
        $request->setUserResolver(fn () => $user);
        $meter = app(UsageMeter::class);
        $first = $meter->reserve($request, 'chat', 'ask', 3);
        $second = $meter->reserve($request, 'chat', 'ask', 5);
        $this->assertIsInt($first['id']);
        $this->assertSame($first['id'] + 1, $second['id']);
        $this->assertSame($first['counter'], $second['counter']);
        $meter->finish($first, false);
        $meter->finish($first, false);
        $this->assertDatabaseHas('usage_counters', [
            'id' => $second['counter'],
            'user_id' => $user->id,
            'messages' => 5,
            'requests' => 1,
        ]);
        $this->actingAs($user)
            ->getJson('/api/account')
            ->assertJsonPath('usage.messages', 5)
            ->assertJsonPath('member.id', $user->id);
    }

    public function test_provider_identifiers_remain_separate_from_subscription_row_ids(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'paypal_id' => 'I-EXTERNAL-PROVIDER',
        ]);
        $this->assertIsInt($subscription->id);
        $this->assertSame('I-EXTERNAL-PROVIDER', $subscription->paypal_id);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'user_id' => $user->id,
            'paypal_id' => 'I-EXTERNAL-PROVIDER',
        ]);
    }
}
