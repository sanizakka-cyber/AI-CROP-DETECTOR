<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Null the mobile API bearer token too -- a stolen token must not
        // outlive the password change it was supposed to be defended by.
        // AuthApiController::logout() already does this; this is the one
        // other place a password actually changes.
        $request->user()->update([
            'password'  => Hash::make($validated['password']),
            'api_token' => null,
        ]);

        return back()->with('status', 'password-updated');
    }
}
