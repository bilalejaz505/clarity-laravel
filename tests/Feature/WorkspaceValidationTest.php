<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkspaceValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_malformed_nested_records_do_not_overwrite_a_saved_workspace(): void
    {
        $user = User::factory()->create(['trial_start' => now(), 'trial_end' => now()->addDay()]);
        $store = ['conversations' => [], 'tasks' => [], 'invoices' => []];
        Workspace::factory()->create(['user_id' => $user->id, 'data' => $store]);
        $this->actingAs($user);

        foreach (['bad root', array_replace($store, ['reviews' => 'bad collection']), array_replace($store, ['tasks' => [['id' => 'bad-task']]])] as $invalid) {
            $this->putJson('/api/workspace', ['store' => $invalid, 'product' => 'chat'])->assertUnprocessable();
            $this->assertSame($store, Workspace::where('user_id', $user->id)->firstOrFail()->data);
        }
    }

    public function test_settings_seed_is_repeatable_and_never_creates_a_shared_login(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('app_settings', 1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_demo_does_not_show_an_account_or_admin_link(): void
    {
        $this->get('/demo')->assertOk()->assertSee('Log in')->assertDontSee('My account')->assertDontSee('Admin panel');
    }

    public function test_expired_account_sees_the_product_gate_instead_of_a_server_error(): void
    {
        $user = User::factory()->create(['trial_start' => now()->subDays(9), 'trial_end' => now()->subDays(2)]);
        $this->actingAs($user)->get('/chats')->assertForbidden()->assertSee('Choose your next step.');
    }
}
