<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    private const SESSION_KEY = 'impersonate.original_id';

    public function impersonate(User $user): \Illuminate\Http\RedirectResponse
    {
        $actor = Auth::user();

        // Only CEO and admin may impersonate; CEO can impersonate anyone;
        // admin cannot impersonate another admin or CEO.
        if (!in_array($actor->role, ['ceo', 'admin'])) {
            abort(403);
        }
        if ($actor->role === 'admin' && in_array($user->role, ['ceo', 'admin'])) {
            abort(403, 'Admins cannot impersonate CEO or other admins.');
        }
        if ($actor->id === $user->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }
        if (!$user->is_active) {
            return back()->with('error', 'That account is inactive.');
        }

        // Prevent nested impersonation
        if (session()->has(self::SESSION_KEY)) {
            return back()->with('error', 'Already impersonating. Leave first.');
        }

        Log::info('Impersonation started', [
            'actor_id'   => $actor->id,
            'actor_role' => $actor->role,
            'target_id'  => $user->id,
            'target_role'=> $user->role,
        ]);

        session()->put(self::SESSION_KEY, $actor->id);
        Auth::loginUsingId($user->id);
        session()->regenerate();

        return redirect('/')->with('success', "Now viewing as {$user->first_name} {$user->last_name} ({$user->role}).");
    }

    public function leave(): \Illuminate\Http\RedirectResponse
    {
        $originalId = session()->pull(self::SESSION_KEY);
        if (!$originalId) {
            return redirect('/')->with('info', 'You are not impersonating anyone.');
        }

        $original = User::find($originalId);
        if (!$original) {
            Auth::logout();
            return redirect('/login')->with('error', 'Original session could not be restored.');
        }

        Log::info('Impersonation ended', [
            'original_id'   => $original->id,
            'original_role' => $original->role,
            'was_id'        => Auth::id(),
        ]);

        Auth::loginUsingId($original->id);
        session()->regenerate();

        return redirect('/admin/dashboard')->with('success', 'Returned to your account.');
    }
}
