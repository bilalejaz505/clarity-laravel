<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedbackAndContractsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_failure_is_available_to_the_notification_component(): void
    {
        $this->from('/login')
            ->followingRedirects()
            ->post('/login', [
                'email' => 'unknown@example.com',
                'password' => 'invalid-password',
            ])
            ->assertOk()
            ->assertSee('clarityFeedbackMessages', false)
            ->assertSee('Unable to log in. Check your email and password.')
            ->assertDontSee('class="error-banner"', false);
    }

    public function test_notification_flash_text_cannot_break_out_of_its_script(): void
    {
        $text = '</script><script>alert("unsafe")</script>';
        $this->withSession(['status' => $text])
            ->get('/login')
            ->assertOk()
            ->assertSee('clarityFeedbackMessages', false)
            ->assertDontSee($text, false);
    }

    public function test_contracts_route_and_old_links_open_the_same_protected_product(): void
    {
        $user = User::factory()->create([
            'trial_start' => now(),
            'trial_end' => now()->addDay(),
        ]);
        $this->actingAs($user)
            ->get('/contracts')
            ->assertOk()
            ->assertViewHas('product', 'freelance')
            ->assertSee('Contract Tracker')
            ->assertSee('Contract Summary')
            ->assertDontSee('Agreement tracker');
        $this->get('/agreements')
            ->assertOk()
            ->assertViewHas('product', 'freelance');
    }

    public function test_contracts_workspace_still_requires_login(): void
    {
        $this->get('/contracts')->assertRedirect('/login');
    }
}
