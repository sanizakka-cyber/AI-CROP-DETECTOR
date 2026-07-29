<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TwoFactorController;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\NewDeviceLoginMail;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Services\OtpService;
use App\Services\TrustedDeviceService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use NormalizesPhone;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Block suspended / deactivated accounts
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'identifier' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        // Block pending/rejected applicants
        $appStatus = $user->application_status ?? 'approved';
        if ($appStatus === 'pending') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'identifier' => 'Your application is currently under review. You will receive an email once a decision has been made.',
            ]);
        }

        if ($appStatus === 'rejected') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $reason = $user->rejection_reason
                ? ' Reason: ' . $user->rejection_reason
                : ' Please contact support for more information.';
            return redirect()->route('login')->withErrors([
                'identifier' => 'Your application was not approved.' . $reason,
            ]);
        }

        // Unverified accounts — send OTP and redirect to verify screen
        if (! $user->email_verified_at && ! $user->phone_verified_at) {
            if (! $user->email && $user->phone) {
                $user->update(['phone_verified_at' => now()]);
                return redirect()->intended(route('dashboard', absolute: false));
            }

            $identifier  = $user->email ?? $user->phone;
            $otp         = app(OtpService::class);
            $plain       = $otp->generate($identifier, 'registration');
            $emailFailed = false;
            $smsFailed   = false;

            if ($user->email) {
                $emailFailed = ! $otp->sendViaEmail($user->notification_email ?? $identifier, $plain, $user->first_name);
            } else {
                $smsFailed = ! $otp->sendViaSms($identifier, $plain);
            }

            $expiresAt = $otp->expiresAt($identifier, 'registration');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            session([
                'otp_context'         => 'registration',
                'otp_identifier'      => $identifier,
                'otp_user_id'         => $user->id,
                'otp_sms_failed'      => $smsFailed,
                'otp_email_failed'    => $emailFailed,
                'otp_expires_at'      => $expiresAt?->toISOString(),
                'otp_delivery_method' => $user->email ? 'email' : 'sms',
            ]);

            return redirect()->route('otp.verify')
                ->with('status', 'Please verify your account first. A new code has been sent.');
        }

        // ── Trusted device check ───────────────────────────────────────────────
        // Only relevant for accounts that would otherwise require 2FA.
        $needs2FA = TwoFactorController::roleRequires2FA($user->role) || $user->two_factor_enabled;

        if ($needs2FA) {
            $tdSvc = app(TrustedDeviceService::class);

            if ($tdSvc->isDeviceTrusted($request, $user)) {
                // Known trusted device — skip 2FA entirely
                $tdSvc->refreshLastUsed($request, $user);
                LoginHistory::record($user->id, true);
                return redirect()->intended(route('dashboard', absolute: false));
            }

            // New device — initiate 2FA and flag for new-device notification
            LoginHistory::record($user->id, true);
            Auth::logout();
            session(['2fa_user_id' => $user->id, '2fa_new_device' => true]);
            TwoFactorController::initiate($user);
            return redirect()->route('2fa.verify');
        }

        // ── Normal (non-2FA) user ──────────────────────────────────────────────
        LoginHistory::record($user->id, true);

        // Send new-device notification if no trusted device is registered for this user at all
        // (first login from any device, or after all trusted devices were revoked)
        $ua = $request->userAgent() ?? '';
        $this->maybeSendNewDeviceEmail($user, $ua, $request->ip() ?? '');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditLog::record('logout', 'User', Auth::id());

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function maybeSendNewDeviceEmail(\App\Models\User $user, string $ua, string $ip): void
    {
        if (! $user->email) {
            return;
        }

        // Only notify if no trusted devices at all (truly first login / post-revoke)
        $hasAnyTrustedDevice = \App\Models\TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();

        if ($hasAnyTrustedDevice) {
            return;
        }

        // Don't notify on the very first ever login (no prior login history)
        $priorLogins = LoginHistory::where('user_id', $user->id)
            ->where('success', true)
            ->count();

        if ($priorLogins <= 1) {
            return;
        }

        try {
            Mail::to($user->notification_email ?? $user->email)->send(new NewDeviceLoginMail(
                $user,
                LoginHistory::parseBrowser($ua),
                LoginHistory::parsePlatform($ua),
                $ip,
                now()->format('M d, Y g:i A') . ' UTC'
            ));
        } catch (\Throwable $e) {
            Log::warning('NewDeviceLoginMail failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
