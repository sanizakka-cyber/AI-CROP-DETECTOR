<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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

        // Block unverified accounts — re-send OTP and redirect to verify
        if (! $user->email_verified_at && ! $user->phone_verified_at) {
            $identifier = $user->email ?? $user->phone;
            $otp        = app(OtpService::class);
            $plain      = $otp->generate($identifier, 'registration');

            if ($user->email) {
                $otp->sendViaEmail($identifier, $plain, $user->first_name);
            } else {
                $otp->sendViaSms($identifier, $plain);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            session([
                'otp_context'    => 'registration',
                'otp_identifier' => $identifier,
                'otp_user_id'    => $user->id,
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
