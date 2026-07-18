<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use NormalizesPhone;

    public function __construct(private OtpService $otp) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $publicRoles = [
            'farmer', 'vet', 'agronomist', 'agro-dealer',
            'equipment-dealer', 'agribusiness-owner', 'cooperative',
            'government-agency', 'ngo', 'research-institution',
            'input-supplier', 'logistics-provider', 'investor', 'general-user',
        ];

        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'identifier'  => 'required|string|max:255',
            'role'        => 'nullable|string|in:' . implode(',', $publicRoles),
            'country'     => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'lga'         => 'nullable|string|max:100',
            'ward'        => 'nullable|string|max:100',
            'password'    => ['required', 'confirmed', Rules\Password::min(8)
                ->mixedCase()->numbers()->symbols()],
        ]);

        $identifier = trim($request->identifier);
        $isEmail    = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isPhone    = ! $isEmail && $this->looksLikePhone($identifier);

        if (! $isEmail && ! $isPhone) {
            return back()->withInput()->withErrors([
                'identifier' => 'Enter a valid email address or phone number (e.g. 08012345678 or +2348012345678).',
            ]);
        }

        // Duplicate check
        if ($isEmail && User::where('email', $identifier)->exists()) {
            return back()->withInput()->withErrors([
                'identifier' => 'An account already exists with this email. Sign in instead.',
            ]);
        }

        $normalizedPhone = $isPhone ? $this->normalizePhone($identifier) : null;

        if ($isPhone && User::where('phone', $normalizedPhone)->exists()) {
            return back()->withInput()->withErrors([
                'identifier' => 'This phone number is already registered. Sign in instead.',
            ]);
        }

        // Create user
        $userData = [
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'role'        => in_array($request->role, $publicRoles) ? $request->role : 'farmer',
            'country'     => $request->country ?: 'Nigeria',
            'state'       => $request->state,
            'lga'         => $request->lga,
            'ward'        => $request->ward,
            'password'    => Hash::make($request->password),
        ];

        if ($isEmail) {
            $userData['email'] = $identifier;
        } else {
            $userData['phone'] = $normalizedPhone;
        }

        $user  = User::create($userData);
        $plain = $this->otp->generate($identifier, 'registration');

        // Send OTP and capture delivery result
        $smsFailed   = false;
        $emailFailed = false;

        if ($isEmail) {
            $emailFailed = ! $this->otp->sendViaEmail($identifier, $plain, $user->first_name, $user->id, 'registration');
        } else {
            $smsFailed = ! $this->otp->sendViaSms($normalizedPhone, $plain, $user->id, 'registration');

            if ($smsFailed) {
                Log::warning('Registration SMS delivery failed — user will see email-fallback prompt', [
                    'user_id'    => $user->id,
                    'phone_hint' => $this->maskPhone($normalizedPhone),
                ]);
            }
        }

        // Store session context
        $expiresAt = $this->otp->expiresAt($identifier, 'registration');

        $request->session()->put([
            'otp_context'         => 'registration',
            'otp_identifier'      => $identifier,
            'otp_user_id'         => $user->id,
            'otp_sms_failed'      => $smsFailed,
            'otp_email_failed'    => $emailFailed,
            'otp_expires_at'      => $expiresAt?->toISOString(),
            'otp_delivery_method' => $isEmail ? 'email' : 'sms',
        ]);

        return redirect()->route('otp.verify');
    }
}
