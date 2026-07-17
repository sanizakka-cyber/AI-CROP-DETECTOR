<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /** Show the new-password form (reached after OTP verification). */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('reset_token') || ! $request->session()->has('reset_user_id')) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Session expired. Please start the reset process again.']);
        }

        return view('auth.reset-password');
    }

    /** Update the password. */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('reset_token') || ! $request->session()->has('reset_user_id')) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Session expired. Please start the reset process again.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::findOrFail($request->session()->get('reset_user_id'));
        $user->update(['password' => Hash::make($request->password)]);

        $request->session()->forget(['reset_token', 'reset_user_id', 'otp_identifier']);

        return redirect()->route('login')
            ->with('status', 'Password updated successfully. Please sign in with your new password.');
    }
}
