<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $query = User::query();
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('phone',      'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role != '') {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        return view('admin.users', compact('users'));
    }

    public function staff()
    {
        $staff = User::whereIn('role', ['admin', 'vet', 'agronomist', 'finance', 'rider', 'hr'])
                     ->latest()
                     ->paginate(15);
        return view('admin.staff', compact('staff'));
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function reports()
    {
        return view('admin.reports');
    }

    public function toggleStatus(User $user)
    {
        // Don't disable yourself or CEO
        if ($user->id === auth()->id() || $user->role === 'ceo') {
            return back()->with('error', 'Cannot change status of this account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with('success', 'User status updated successfully.');
    }

    public function deleteUser(User $user)
    {
        // Don't delete yourself or CEO
        if ($user->id === auth()->id() || $user->role === 'ceo') {
            return back()->with('error', 'Cannot delete this account.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    public function storeUser(Request $request)
    {
        $staffRoles = [
            'vet', 'agronomist', 'admin', 'finance', 'hr', 'operations',
            'data-analyst', 'm-e-officer', 'field-officer', 'customer-support',
            'extension-officer',
        ];

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'       => ['required', 'string', 'in:' . implode(',', $staffRoles)],
            'password'   => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'first_name'          => $validated['first_name'],
            'last_name'           => $validated['last_name'],
            'email'               => $validated['email'],
            'role'                => $validated['role'],
            'password'            => Hash::make($validated['password']),
            'is_active'           => true,
            'force_password_reset'=> true,
        ]);

        Log::info('Admin created staff account', [
            'created_by' => auth()->id(),
            'new_user_id'=> $user->id,
            'role'       => $user->role,
        ]);

        return redirect()->route('admin.users')
            ->with('success', "Account created for {$user->name}. They must change their password on first login.");
    }
}
