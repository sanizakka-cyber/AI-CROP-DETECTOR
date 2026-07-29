<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    use NormalizesPhone;

    public function __construct(private OtpService $otp) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)
                ->orWhere('phone', $this->normalizePhone($identifier))
                ->first();

        if (! $user) {
            // Generic message — avoids confirming whether an account exists
            return back()->withErrors([
                'identifier' => 'If an account exists with that identifier, a reset code has been sent.',
            ]);
        }

        $resolvedIdentifier = $isEmail ? $user->email : $user->phone;
        $plain = $this->otp->generate($resolvedIdentifier, 'password_reset');

        if ($isEmail) {
            $this->otp->sendViaEmail($resolvedIdentifier, $plain, $user->first_name, $user->id, 'password_reset');
        } else {
            $this->otp->sendViaSms($resolvedIdentifier, $plain, $user->id, 'password_reset');
        }

        try {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'otp.sent',
                'model'      => 'User',
                'model_id'   => $user->id,
                'details'    => json_encode(['context' => 'password_reset']),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable) {}

        $expiresAt = $this->otp->expiresAt($resolvedIdentifier, 'password_reset');

        $request->session()->put([
            'otp_context'         => 'password_reset',
            'otp_identifier'      => $resolvedIdentifier,
            'otp_user_id'         => $user->id,   // consistent key used across all OTP contexts
            'reset_user_id'       => $user->id,   // kept for backwards compatibility
            'otp_delivery_method' => $isEmail ? 'email' : 'sms',
            'otp_expires_at'      => $expiresAt?->toISOString(),
        ]);

        return redirect()->route('otp.verify');
    }
}
