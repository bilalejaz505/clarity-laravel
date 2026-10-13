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
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin sign in.')
            ->assertDontSee('Create an account');
    }

    public function test_member_credentials_cannot_start_an_admin_session(): void
    {
        $member = User::factory()->create(['password' => 'member-password']);
        $this->post('/admin/login', [
            'email' => $member->email,
            'password' => 'member-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_verified_admin_credentials_go_directly_to_administration(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => 'admin-password',
        ]);
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-password',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/login')->assertRedirect('/admin');
    }

    public static function restrictedAdminStates(): array
    {
        return [
            'suspended' => [['status' => 'suspended']],
            'unverified' => [['email_verified_at' => null]],
        ];
    }

    #[DataProvider('restrictedAdminStates')]
    public function test_suspended_and_unverified_admins_cannot_sign_in(
        array $state,
    ): void {
        $admin = User::factory()->create(
            array_merge(
                ['is_admin' => true, 'password' => 'admin-password'],
                $state,
            ),
        );
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_local_initialization_creates_package_scoped_accounts(): void
    {
        $this->freezeTime();
        $this->app->instance('env', 'local');
        User::factory()->count(3)->create();
        (new LocalWorkspaceSeeder([
            'admin' => 'private-admin-pass',
            'chat' => 'private-chat-pass',
            'freelance' => 'private-contracts-pass',
            'bundle' => 'private-complete-pass',
        ]))->run();
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('workspaces', 4);
        $this->assertDatabaseCount('subscriptions', 3);
        $admin = User::where('email', 'admin@clarity.com')->firstOrFail();
        $chatMember = User::where('email', 'chat@clarity.com')->firstOrFail();
        $contractsMember = User::where(
            'email',
            'contracts@clarity.com',
        )->firstOrFail();
        $completeMember = User::where(
            'email',
            'complete@clarity.com',
        )->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertFalse($chatMember->is_admin);
        $this->assertTrue($chatMember->hasVerifiedEmail());
        $this->assertNull($chatMember->trial_end);
        $store = Workspace::where('user_id', $chatMember->id)->firstOrFail()
            ->data;
        $this->assertCount(2, $store['conversations']);
        $this->assertCount(2, $store['tasks']);
        $this->assertCount(2, $store['invoices']);
        $this->assertCount(3, app(AppSettingsService::class)->get()['plans']);
        $this->actingAs($chatMember)->get('/chats')->assertOk();
        $this->get('/contracts')
            ->assertForbidden()
            ->assertSeeText('does not include Contracts');
        $this->actingAs($contractsMember)->get('/contracts')->assertOk();
        $this->get('/chats')
            ->assertForbidden()
            ->assertSeeText('does not include Chat Insights');
        $this->actingAs($completeMember)
            ->get('/contracts')
            ->assertOk()
            ->assertViewHas(
                'initialStore',
                fn (array $value): bool => count($value['conversations']) === 2,
            );
        $this->get('/chats')->assertOk();
    }

    public function test_local_reset_command_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('clarity:reset-local-data', [
            '--reset' => true,
        ])->assertFailed();
    }
}
