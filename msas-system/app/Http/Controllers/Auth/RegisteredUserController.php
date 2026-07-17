<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
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
                'identifier' => 'Enter a valid email address or phone number.',
            ]);
        }

        // Duplicate check
        if ($isEmail && User::where('email', $identifier)->exists()) {
            return back()->withInput()->withErrors([
                'identifier' => 'An account already exists with this email. Sign in instead.',
            ]);
        }
        if ($isPhone && User::where('phone', $this->normalizePhone($identifier))->exists()) {
            return back()->withInput()->withErrors([
                'identifier' => 'Phone number already registered. Sign in instead.',
            ]);
        }

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
            $userData['phone'] = $this->normalizePhone($identifier);
        }

        $user  = User::create($userData);
        $plain = $this->otp->generate($identifier, 'registration');

        if ($isEmail) {
            $this->otp->sendViaEmail($identifier, $plain, $user->first_name);
        } else {
            $this->otp->sendViaSms($this->normalizePhone($identifier), $plain);
        }

        $request->session()->put([
            'otp_context'    => 'registration',
            'otp_identifier' => $identifier,
            'otp_user_id'    => $user->id,
        ]);

        return redirect()->route('otp.verify');
    }

    private function looksLikePhone(string $input): bool
    {
        $clean = preg_replace('/[\s\-\(\)]/', '', $input);
        return (bool) preg_match('/^(\+?234|0)[789]\d{9}$/', $clean);
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
