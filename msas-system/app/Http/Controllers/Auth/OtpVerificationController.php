<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    use NormalizesPhone;

    public function __construct(private OtpService $otp) {}

    /** Show the OTP entry screen. */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_identifier')) {
            return redirect()->route('login');
        }

        $identifier = $request->session()->get('otp_identifier');
        $expiresAt  = $request->session()->get('otp_expires_at');

        return view('auth.verify-otp', [
            'identifier'     => $identifier,
            'context'        => $request->session()->get('otp_context', 'registration'),
            'smsFailed'      => $request->session()->get('otp_sms_failed', false),
            'emailFailed'    => $request->session()->get('otp_email_failed', false),
            'deliveryMethod' => $request->session()->get('otp_delivery_method', 'email'),
            'expiresAt'      => $expiresAt,    // ISO 8601 string — JS uses this for accurate countdown
        ]);
    }

    /** Verify the submitted OTP. */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/']]);

        $identifier = $request->session()->get('otp_identifier');
        $context    = $request->session()->get('otp_context', 'registration');

        if (! $identifier) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please start again.']);
        }

        try {
            $this->otp->verify($identifier, $context, $request->code);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        if ($context === 'registration') {
            $userId = $request->session()->get('otp_user_id');
            $user   = User::findOrFail($userId);

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->update(['email_verified_at' => now()]);
            } else {
                $user->update(['phone_verified_at' => now()]);
            }

            $this->clearOtpSession($request);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('status', 'Account verified! Welcome to MSAS.');
        }

        if ($context === 'password_reset') {
            $token = \Illuminate\Support\Str::random(64);
            $request->session()->put('reset_token', $token);
            $request->session()->forget('otp_context');

            return redirect()->route('password.reset.form');
        }

        return redirect()->route('login');
    }

    /** Resend OTP to the current identifier (same channel). */
    public function resend(Request $request): RedirectResponse
    {
        $identifier = $request->session()->get('otp_identifier');
        $context    = $request->session()->get('otp_context', 'registration');
        $method     = $request->session()->get('otp_delivery_method', 'email');

        if (! $identifier) {
            return redirect()->route('login');
        }

        $firstName = 'User';
        if ($uid = $request->session()->get('otp_user_id')) {
            $firstName = User::find($uid)?->first_name ?? 'User';
        }

        $plain     = $this->otp->generate($identifier, $context);
        $expiresAt = $this->otp->expiresAt($identifier, $context);

        $failed = false;

        if ($method === 'sms') {
            $normalized = $this->looksLikePhone($identifier)
                ? $this->normalizePhone($identifier)
                : $identifier;
            $failed = ! $this->otp->sendViaSms($normalized, $plain);
        } else {
            $failed = ! $this->otp->sendViaEmail($identifier, $plain, $firstName);
        }

        $request->session()->put([
            'otp_sms_failed'   => $method === 'sms' && $failed,
            'otp_email_failed' => $method === 'email' && $failed,
            'otp_expires_at'   => $expiresAt?->toISOString(),
        ]);

        if ($failed) {
            return back()->withErrors(['code' => 'We couldn\'t resend the code. Please try again or switch to a different method.']);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    /**
     * Switch from failed SMS to email verification.
     * Accepts the user's email, sends a fresh OTP to it, and updates the session.
     */
    public function switchToEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'fallback_email' => 'required|email|max:255',
        ]);

        $userId  = $request->session()->get('otp_user_id');
        $context = $request->session()->get('otp_context', 'registration');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['fallback_email' => 'Session expired. Please register again.']);
        }

        $user  = User::findOrFail($userId);
        $email = strtolower(trim($request->fallback_email));

        // Prevent using an email that belongs to a different account
        $taken = User::where('email', $email)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            return back()->withErrors(['fallback_email' => 'That email address is already in use by another account.']);
        }

        // Persist email on user record so verification can activate it
        $user->update(['email' => $email]);

        // Generate new OTP for the email identifier
        $plain     = $this->otp->generate($email, $context);
        $expiresAt = $this->otp->expiresAt($email, $context);
        $sent      = $this->otp->sendViaEmail($email, $plain, $user->first_name);

        Log::info('OTP fallback to email', [
            'user_id'    => $user->id,
            'email_hint' => substr($email, 0, 3) . '***@' . explode('@', $email)[1],
            'sent'       => $sent,
        ]);

        $request->session()->put([
            'otp_identifier'      => $email,
            'otp_sms_failed'      => false,
            'otp_email_failed'    => ! $sent,
            'otp_delivery_method' => 'email',
            'otp_expires_at'      => $expiresAt?->toISOString(),
        ]);

        if (! $sent) {
            return redirect()->route('otp.verify')
                ->withErrors(['code' => 'Failed to send verification email. Please check the address and try again.']);
        }

        return redirect()->route('otp.verify')
            ->with('status', 'Verification code sent to ' . $email . '. Please check your inbox.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function clearOtpSession(Request $request): void
    {
        $request->session()->forget([
            'otp_identifier', 'otp_context', 'otp_user_id',
            'otp_sms_failed', 'otp_email_failed',
            'otp_expires_at', 'otp_delivery_method',
        ]);
    }
}
