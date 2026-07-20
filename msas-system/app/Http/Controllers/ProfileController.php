<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:100'],
            'middle_name'   => ['nullable', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone'         => ['nullable', 'string', 'max:20'],
            'state'         => ['nullable', 'string', 'max:100'],
            'lga'           => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if it exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $validated['profile_photo'] = $path;
        }

        $user->fill(array_filter($validated, fn($v) => $v !== null || array_key_exists('middle_name', $validated)));

        // Explicitly set nullable fields
        $user->first_name  = $validated['first_name'];
        $user->middle_name = $validated['middle_name'] ?? null;
        $user->last_name   = $validated['last_name'];
        $user->email       = $validated['email'];
        $user->phone       = $validated['phone'] ?? null;
        $user->state       = $validated['state'] ?? null;
        $user->lga         = $validated['lga'] ?? null;

        if (isset($validated['profile_photo'])) {
            $user->profile_photo = $validated['profile_photo'];
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Profile updated successfully.',
                'photo_url' => $user->avatar_url,
                'name'      => trim($user->first_name . ' ' . $user->last_name),
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function changePasswordForm(Request $request): View
    {
        return view('profile.change-password', ['user' => $request->user()]);
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $rules = [
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ];

        // Require current password unless admin forced a reset
        if (! $request->user()->force_password_reset) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules);

        $user = $request->user();
        $user->password             = Hash::make($request->password);
        $user->force_password_reset = false;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'password-changed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Clean up profile photo on delete
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
