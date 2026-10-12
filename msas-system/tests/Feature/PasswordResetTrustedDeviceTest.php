<?php

namespace Tests\Feature;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8, Section 8 (security/data-integrity): a trusted device skips
 * 2FA entirely (AuthenticatedSessionController::store() -- "Known
 * trusted device -- skip 2FA entirely"). ProfileController::
 * changePassword() already revokes all trusted devices on a voluntary
 * password change, with an explicit comment that a password change
 * must force re-verification everywhere. NewPasswordController::store()
 * (the forgot-password reset flow -- the path a user takes specifically
 * when they suspect compromise) was missing that same revocation, so a
 * device trusted before the reset could still skip 2FA after it.
 */
class PasswordResetTrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_forgot_password_reset_revokes_all_trusted_devices(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        TrustedDevice::create([
            'user_id' => $user->id, 'token_hash' => hash('sha256', 'some-token'),
            'device_name' => 'Test Device', 'browser' => 'Test', 'platform' => 'Test',
            'ip_address' => '127.0.0.1', 'last_used_at' => now(), 'expires_at' => now()->addDays(30),
        ]);

        $this->assertSame(1, TrustedDevice::where('user_id', $user->id)->count());

        $this->withSession([
            'reset_token'   => 'TEST-RESET-TOKEN',
            'reset_user_id' => $user->id,
        ])->post(route('password.store'), [
            'reset_token'           => 'TEST-RESET-TOKEN',
            'password'              => 'NewStrongP@ssw0rd1',
            'password_confirmation' => 'NewStrongP@ssw0rd1',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, TrustedDevice::where('user_id', $user->id)->count(), 'A password reset must revoke every previously-trusted device, the same as a voluntary password change does.');
        $this->assertNull($user->refresh()->api_token);
    }
}
