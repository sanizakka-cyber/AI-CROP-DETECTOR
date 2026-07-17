<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    /** Show the OTP entry screen. */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_identifier')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp', [
            'identifier' => $request->session()->get('otp_identifier'),
            'context'    => $request->session()->get('otp_context', 'registration'),
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

            // Mark the correct channel as verified
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->update(['email_verified_at' => now()]);
            } else {
                $user->update(['phone_verified_at' => now()]);
            }

            $request->session()->forget(['otp_identifier', 'otp_context', 'otp_user_id']);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('status', 'Account verified! Welcome to MSAS.');
        }

        if ($context === 'password_reset') {
            // Store a short-lived reset token and send to new-password form
            $token = \Illuminate\Support\Str::random(64);
            $request->session()->put('reset_token', $token);
            $request->session()->forget('otp_context');

            return redirect()->route('password.reset.form');
        }

        return redirect()->route('login');
    }

    /** Resend OTP to the same identifier. */
    public function resend(Request $request): RedirectResponse
    {
        $identifier = $request->session()->get('otp_identifier');
        $context    = $request->session()->get('otp_context', 'registration');

        if (! $identifier) {
            return redirect()->route('login');
        }

        $firstName = 'User';
        if ($uid = $request->session()->get('otp_user_id')) {
            $firstName = User::find($uid)?->first_name ?? 'User';
        }

        $plain = $this->otp->generate($identifier, $context);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->otp->sendViaEmail($identifier, $plain, $firstName);
        } else {
            $this->otp->sendViaSms($identifier, $plain);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }
}
