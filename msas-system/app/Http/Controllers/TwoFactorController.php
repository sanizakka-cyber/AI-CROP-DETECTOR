<?php

namespace App\Http\Controllers;

use App\Mail\NewDeviceLoginMail;
use App\Models\LoginHistory;
use App\Services\TrustedDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorController extends Controller
{
    // Roles that always require 2FA on unrecognised devices
    const REQUIRED_ROLES = ['ceo', 'admin', 'finance', 'hr', 'operations'];

    public static function roleRequires2FA(string $role): bool
    {
        return in_array($role, self::REQUIRED_ROLES);
    }

    // Called right after login for privileged accounts — stores a hashed code, sends email
    public static function initiate($user): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'two_factor_code'       => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        try {
            $mailTo = $user->notification_email ?? $user->email;
            Mail::send('emails.two-factor', ['code' => $code, 'user' => $user], function ($m) use ($mailTo) {
                $m->to($mailTo)->subject('Your MSAS FarmAI Security Code');
            });
        } catch (\Throwable $e) {
            Log::warning('2FA email failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    // Show OTP entry form
    public function showVerify()
    {
        $userId = session('2fa_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (! $user || ($user->two_factor_expires_at && now()->gt($user->two_factor_expires_at))) {
            session()->forget(['2fa_user_id', '2fa_new_device']);
            return redirect()->route('login')->withErrors(['identifier' => 'Your security code has expired. Please log in again.']);
        }

        return view('auth.two-factor');
    }

    // Cancel pending 2FA — clears session so the user is not trapped in a loop
    public function cancel()
    {
        session()->forget(['2fa_user_id', '2fa_new_device']);
        return redirect()->route('login');
    }

    // Verify submitted OTP
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $userId = session('2fa_user_id');
        if (! $userId) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please log in again.']);
        }

        $user = \App\Models\User::findOrFail($userId);

        if ($user->two_factor_expires_at && now()->gt($user->two_factor_expires_at)) {
            session()->forget(['2fa_user_id', '2fa_new_device']);
            return redirect()->route('login')->withErrors(['code' => 'Code expired. Please log in again to receive a new code.']);
        }

        if (! Hash::check($request->code, $user->two_factor_code)) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        // Pull new-device flag before clearing session
        $isNewDevice = session()->pull('2fa_new_device', false);

        // Clear 2FA state and complete login
        $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
        session()->forget('2fa_user_id');

        Auth::login($user);
        LoginHistory::record($user->id, true);

        $response = redirect()->intended(route('dashboard', absolute: false));

        // Optionally trust this device for 30 days
        if ($request->boolean('trust_device')) {
            try {
                $cookie   = app(TrustedDeviceService::class)->trustDevice($request, $user);
                $response = $response->withCookie($cookie);
                $isNewDevice = false; // trusted — don't send new-device alert
            } catch (\Throwable $e) {
                Log::warning('trustDevice failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        // Send new-device sign-in notification
        if ($isNewDevice && $user->email) {
            try {
                $ua = $request->userAgent() ?? '';
                Mail::to($user->notification_email ?? $user->email)->send(new NewDeviceLoginMail(
                    $user,
                    LoginHistory::parseBrowser($ua),
                    LoginHistory::parsePlatform($ua),
                    $request->ip() ?? '',
                    now()->format('M d, Y g:i A') . ' UTC'
                ));
            } catch (\Throwable $e) {
                Log::warning('NewDeviceLoginMail failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        return $response;
    }

    // Resend code — preserves 2fa_new_device in session
    public function resend()
    {
        $userId = session('2fa_user_id');
        if (! $userId) {
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
        $user->update(['two_factor_enabled' => ! $user->two_factor_enabled]);
        $state = $user->two_factor_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Two-factor authentication {$state}.");
    }
}
