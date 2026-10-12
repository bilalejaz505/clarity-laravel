<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\AppSettingsService;
use Database\Seeders\LocalWorkspaceSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_admin_access_uses_the_dedicated_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Admin sign in.')->assertDontSee('Create an account');
    }

    public function test_member_credentials_cannot_start_an_admin_session(): void
    {
        $member = User::factory()->create(['password' => 'member-password']);
        $this->post('/admin/login', ['email' => $member->email, 'password' => 'member-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_verified_admin_credentials_go_directly_to_administration(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'admin-password']);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'admin-password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/login')->assertRedirect('/admin');
    }

    public static function restrictedAdminStates(): array
    {
        return ['suspended' => [['status' => 'suspended']], 'unverified' => [['email_verified_at' => null]]];
    }

    #[DataProvider('restrictedAdminStates')]
    public function test_suspended_and_unverified_admins_cannot_sign_in(array $state): void
    {
        $admin = User::factory()->create(array_merge(['is_admin' => true, 'password' => 'admin-password'], $state));
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'admin-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_local_initialization_replaces_old_users_with_two_usable_accounts(): void
    {
        $this->freezeTime();
        $this->app->instance('env', 'local');
        User::factory()->count(3)->create();
        (new LocalWorkspaceSeeder('private-admin-pass', 'private-member-pass'))->run();
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('workspaces', 2);
        $this->assertDatabaseCount('subscriptions', 0);
        $admin = User::where('email', 'admin@clarity.local')->firstOrFail();
        $member = User::where('email', 'user@clarity.local')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertFalse($member->is_admin);
        $this->assertTrue($member->hasVerifiedEmail());
        $this->assertTrue($member->trial_end->isFuture());
        $store = Workspace::findOrFail($member->id)->data;
        $this->assertCount(2, $store['conversations']);
        $this->assertCount(2, $store['tasks']);
        $this->assertCount(2, $store['invoices']);
        $this->assertCount(3, app(AppSettingsService::class)->get()['plans']);
        $this->actingAs($member)->get('/agreements')->assertOk()->assertViewHas('initialStore', $store);
    }

    public function test_local_reset_command_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('clarity:reset-local-data', ['--reset' => true])->assertFailed();
    }
}
