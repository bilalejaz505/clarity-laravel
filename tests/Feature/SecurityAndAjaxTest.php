<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_responses_include_security_headers(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString(
            "object-src 'none'",
            $response->headers->get('Content-Security-Policy'),
        );
        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            $response->headers->get('Content-Security-Policy'),
        );
        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-[^']+' blob:/",
            $response->headers->get('Content-Security-Policy'),
        );
        $this->assertStringNotContainsString(
            "script-src 'self' 'unsafe-inline'",
            $response->headers->get('Content-Security-Policy'),
        );
        preg_match(
            "/script-src 'self' 'nonce-([^']+)'/",
            $response->headers->get('Content-Security-Policy'),
            $nonce,
        );
        $response->assertSee('nonce="'.$nonce[1].'"', false);
    }

    public function test_authentication_forms_return_consistent_ajax_responses(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Unable to log in. Check your email and password.',
            )
            ->assertJsonValidationErrors('email');

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Signed in successfully.')
            ->assertJsonPath('redirect', url('/account'));
        $this->assertAuthenticatedAs($user);

        $this->postJson('/logout')
            ->assertOk()
            ->assertJsonPath('message', 'You have been logged out.')
            ->assertJsonPath('redirect', url('/login'));
        $this->assertGuest();
    }

    public function test_ajax_validation_returns_errors_for_each_invalid_field(): void
    {
        $this->postJson('/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
            ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('data-ajax-form', false)
            ->assertSee('js/ajax-forms.js', false);
    }

    public function test_blade_escapes_stored_user_content(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $user = User::factory()->create(['name' => $payload]);

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee(e($payload), false);
    }

    public function test_admin_search_treats_sql_metacharacters_as_plain_text(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['email' => 'ordinary@example.com']);

        $this->actingAs($admin)
            ->getJson('/api/admin?search='.urlencode("%' OR 1=1 --"))
            ->assertOk()
            ->assertJsonCount(0, 'users');
    }
}
