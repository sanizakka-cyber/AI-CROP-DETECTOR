<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Rewritten 2026-10-09 (Phase 6). The stock Breeze version assumed Laravel's
 * default token-based "click a link in your email" reset flow
 * (Illuminate\Auth\Notifications\ResetPassword, /reset-password/{token}).
 * This app replaced that entirely with an OTP-code flow — confirmed by
 * reading the real route/controller chain:
 *   PasswordResetLinkController::store() (/forgot-password) generates a
 *   6-digit code via OtpService and emails it through OtpMail, never
 *   touching Illuminate's ResetPassword notification at all.
 *   OtpVerificationController::verify() (/verify-otp) checks the code and,
 *   for a password_reset context, puts a random reset_token in the
 *   session and redirects to password.reset.form.
 *   NewPasswordController::store() (/reset-password) requires that same
 *   reset_token to be echoed back in the POST body before it will accept
 *   a new password.
 * None of the three original tests could ever pass against this flow —
 * not because the flow is broken, but because they were testing a
 * different, unused flow. These tests now exercise the real one.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_is_sent_for_a_known_identifier(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['identifier' => $user->email]);

        Mail::assertSent(OtpMail::class, function (OtpMail $mail) {
            return $mail->otpType === 'password_reset';
        });
    }

    public function test_reset_password_form_can_be_reached_after_otp_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['identifier' => $user->email]);

        $code = $this->capturePasswordResetOtp();

        $this->post('/verify-otp', ['code' => $code])
            ->assertRedirect(route('password.reset.form'));

        $this->get('/reset-password')->assertStatus(200);
    }

    public function test_password_can_be_reset_with_a_valid_otp_and_reset_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['identifier' => $user->email]);

        $code = $this->capturePasswordResetOtp();

        $this->post('/verify-otp', ['code' => $code]);

        // NewPasswordController::store() requires the session's reset_token
        // to be echoed back in the request body — it is not a URL
        // parameter the way Laravel's stock reset-link token is.
        $resetToken = session('reset_token');
        $this->assertNotNull($resetToken, 'OTP verification must seed a reset_token for the next step.');

        $response = $this->post('/reset-password', [
            'reset_token' => $resetToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        // The new password actually works.
        $this->post('/login', [
            'login' => $user->email,
            'password' => 'NewPassword123!',
        ]);
        $this->assertAuthenticated();
    }

    public function test_password_reset_rejects_a_mismatched_reset_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['identifier' => $user->email]);
        $code = $this->capturePasswordResetOtp();
        $this->post('/verify-otp', ['code' => $code]);

        $response = $this->post('/reset-password', [
            'reset_token' => 'a-token-that-does-not-match-the-session-value',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('password.request'));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('NewPassword123!', $user->fresh()->password));
    }

    /** Reads the plain OTP code out of the faked OtpMail — the real code is only ever stored hashed in the `otps` table. */
    private function capturePasswordResetOtp(): string
    {
        $code = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
            $code = $mail->code;
            return $mail->otpType === 'password_reset';
        });
        $this->assertNotNull($code, 'The password-reset OTP email must actually be sent.');

        return $code;
    }
}
