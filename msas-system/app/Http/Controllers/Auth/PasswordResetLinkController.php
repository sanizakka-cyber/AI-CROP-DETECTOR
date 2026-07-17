<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
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

        // Find user by email or phone
        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)
                ->orWhere('phone', $this->normalizePhone($identifier))
                ->first();

        if (! $user) {
            return back()->withErrors([
                'identifier' => $isEmail
                    ? 'No account found with that email address.'
                    : 'No account found with that phone number.',
            ]);
        }

        $resolvedIdentifier = $isEmail ? $user->email : $user->phone;
        $plain = $this->otp->generate($resolvedIdentifier, 'password_reset');

        if ($isEmail) {
            $this->otp->sendViaEmail($resolvedIdentifier, $plain, $user->first_name);
        } else {
            $this->otp->sendViaSms($resolvedIdentifier, $plain);
        }

        $request->session()->put([
            'otp_context'    => 'password_reset',
            'otp_identifier' => $resolvedIdentifier,
            'reset_user_id'  => $user->id,
        ]);

        return redirect()->route('otp.verify');
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '234' . substr($clean, 1);
        }
        return '+' . ltrim($clean, '+');
    }
}
