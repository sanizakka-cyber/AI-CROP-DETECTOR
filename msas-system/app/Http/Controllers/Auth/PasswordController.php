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

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        // api_token is deliberately excluded from $fillable (see
        // User::createToken()'s own comment), so update(['api_token' =>
        // null]) is silently dropped -- direct property assignment is
        // required. Nulling it too: a stolen mobile token must not
        // outlive the password change it was supposed to be defended by.
        // AuthApiController::logout() already does this; this is the one
        // other place a password actually changes.
        $user->revokeAllApiTokens(); // every device, not just the newest token
        $user->save();

        return back()->with('status', 'password-updated');
    }
}
