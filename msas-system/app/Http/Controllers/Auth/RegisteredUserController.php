<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
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
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'required|string|max:20|unique:users,phone',
            'role'        => 'nullable|string|in:' . implode(',', $publicRoles),
            'country'     => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'lga'         => 'nullable|string|max:100',
            'ward'        => 'nullable|string|max:100',
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'role'        => in_array($request->role, $publicRoles) ? $request->role : 'farmer',
            'country'     => $request->country ?: 'Nigeria',
            'state'       => $request->state,
            'lga'         => $request->lga,
            'ward'        => $request->ward,
            'password'    => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
