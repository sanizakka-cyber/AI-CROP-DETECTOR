<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Rewritten 2026-10-09 (Phase 6). The stock version posted a 'name'
     * field (this app has never had that column — only first_name/
     * middle_name/last_name) and 'identifier'-less credentials that don't
     * exist in RegisteredUserController::store()'s real validation rules,
     * then asserted immediate authentication. Immediate authentication is
     * also the wrong expectation for an email registration: the real flow
     * (RegisteredUserController::store(), lines ~139-171) deliberately
     * does NOT log the user in — it sends an OTP and redirects to
     * otp.verify, and only OtpVerificationController::verify() logs the
     * user in once the code is confirmed. This test now exercises that
     * real, intended two-step flow end to end instead of asserting a
     * one-step flow the app was never built to have.
     */
    public function test_new_users_can_register_and_must_verify_otp_before_being_authenticated(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'identifier' => 'test@example.com',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Not authenticated yet — registration only creates the account
        // and queues OTP verification; it does not log the user in.
        $this->assertGuest();
        $response->assertRedirect(route('otp.verify'));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user, 'RegisteredUserController::store() must create the account even though login is deferred.');
        $this->assertNull($user->email_verified_at, 'A freshly registered account must not be pre-verified.');

        $code = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
            $code = $mail->code;
            return $mail->otpType === 'registration';
        });
        $this->assertNotNull($code, 'The registration OTP email must actually be sent.');

        $verifyResponse = $this->post('/verify-otp', ['code' => $code]);

        $this->assertAuthenticated();
        $verifyResponse->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * Phone-only registration has no email to verify, so the real flow
     * (RegisteredUserController::store(), the $isPhone branch) logs the
     * user in immediately instead of routing through OTP — the one case
     * where "register implies authenticated" is actually correct.
     */
    public function test_phone_only_registration_authenticates_immediately(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'identifier' => '+2348012345678',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
