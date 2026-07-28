<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TwoFactorController extends Controller
{
    // Roles that require 2FA
    const REQUIRED_ROLES = ['ceo','admin','finance','hr','operations'];

    public static function roleRequires2FA(string $role): bool
    {
        return in_array($role, self::REQUIRED_ROLES);
    }

    // Called right after login for privileged accounts
    public static function initiate($user): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // Store a bcrypt hash — same pattern as OtpService — so DB read access
        // does not expose a valid code for CEO/admin/finance accounts.
        $user->update([
            'two_factor_code'       => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::send('emails.two-factor', ['code' => $code, 'user' => $user], function ($m) use ($user) {
                $m->to($user->email)->subject('Your MSAS FarmAI Security Code');
            });
        } catch (\Throwable $e) {
            \Log::warning('2FA email failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    // Show OTP entry form
    public function showVerify()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    // Verify submitted OTP
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please log in again.']);
        }

        $user = \App\Models\User::findOrFail($userId);

        if ($user->two_factor_expires_at && now()->gt($user->two_factor_expires_at)) {
            session()->forget('2fa_user_id');
            return redirect()->route('login')->withErrors(['code' => 'Code expired. Please log in again to receive a new code.']);
        }

        if (! Hash::check($request->code, $user->two_factor_code)) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        // Clear 2FA code and complete login
        $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
        session()->forget('2fa_user_id');

        Auth::login($user);

        \App\Models\LoginHistory::record($user->id, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // Resend code
    public function resend()
    {
        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }
        $user = \App\Models\User::findOrFail($userId);
        self::initiate($user);
        return back()->with('status', 'A new code has been sent to your email.');
    }

    // Enable/disable 2FA from security settings
    public function toggle(Request $request)
    {
        $user = auth()->user();
        $user->update(['two_factor_enabled' => !$user->two_factor_enabled]);
        $state = $user->two_factor_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Two-factor authentication {$state}.");
    }
}
