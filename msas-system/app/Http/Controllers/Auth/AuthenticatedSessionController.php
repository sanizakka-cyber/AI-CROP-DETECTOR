<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use NormalizesPhone;
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Block pending/rejected applicants from accessing the platform
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

        // Block unverified accounts — phone-only users get verified immediately (no SMS OTP policy);
        // email users are redirected to OTP verify with the full session payload.
        if (! $user->email_verified_at && ! $user->phone_verified_at) {
            // Phone-only account: activate in place — consistent with registration policy
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
                $emailFailed = ! $otp->sendViaEmail($identifier, $plain, $user->first_name);
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

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
