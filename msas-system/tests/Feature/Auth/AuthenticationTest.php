<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Fixed 2026-10-09 (Phase 6): this test posted 'email', but the real
     * login form (LoginRequest::rules()) validates a field named 'login'
     * (accepting either an email or a phone number) — there is no 'email'
     * key in the request at all. The stock Breeze test was never updated
     * when this app's login was customized to accept both identifiers,
     * so every submission failed validation before Auth::attempt() ever
     * ran, and the user was never authenticated. This was a stale test,
     * not an application defect — LoginRequest::authenticate() itself
     * works correctly once given the field it actually expects.
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /** Companion to the fixed email case above — the same LoginRequest accepts a phone number as 'login' too. */
    public function test_users_can_authenticate_with_a_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '+2348012345678']);

        $response = $this->post('/login', [
            'login' => '+2348012345678',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
