<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /** Show the new-password form (reached after OTP verification). */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('reset_token') || ! $request->session()->has('reset_user_id')) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Session expired. Please start the reset process again.']);
        }

        return view('auth.reset-password');
    }

    /**
     * Entry point for the signed link in StaffWelcomeMail. This page (and
     * password.store below it) has always been session-driven — it reads
     * session('reset_token')/session('reset_user_id'), normally populated
     * only after OTP verification (OtpVerificationController::verify()).
     * A one-time staff invite has no OTP step to go through, so this
     * bootstraps the same two session keys directly once the link's
     * signature is verified, then hands off to the existing form.
     */
    public function beginStaffFirstLogin(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'This link is invalid or has expired. Please contact your administrator for a new one.',
            ]);
        }

        // Idempotent — if they already finished first-login setup, a
        // stale/reused link just sends them to sign in normally instead of erroring.
        if (! $user->force_password_reset) {
            return redirect()->route('login')->with('status', 'Your password has already been set. Please sign in.');
        }

        $request->session()->put('reset_token', \Illuminate\Support\Str::random(64));
        $request->session()->put('reset_user_id', $user->id);

        return redirect()->route('password.reset.form');
    }

    /** Update the password. */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('reset_token') || ! $request->session()->has('reset_user_id')) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Session expired. Please start the reset process again.']);
        }

        // The token was generated at OTP-verification time and round-tripped
        // through a hidden form field — this was previously generated but
        // never actually compared against anything, making it decorative.
        if (! hash_equals((string) $request->session()->get('reset_token'), (string) $request->input('reset_token'))) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Invalid or expired reset session. Please start again.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::findOrFail($request->session()->get('reset_user_id'));
        $user->update(['password' => Hash::make($request->password)]);

        $request->session()->forget(['reset_token', 'reset_user_id', 'otp_identifier']);

        return redirect()->route('login')
            ->with('status', 'Password updated successfully. Please sign in with your new password.');
    }
}
