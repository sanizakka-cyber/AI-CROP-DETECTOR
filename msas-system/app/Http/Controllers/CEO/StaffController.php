<?php

namespace App\Http\Controllers\CEO;

use App\Http\Controllers\Controller;
use App\Mail\StaffWelcomeMail;
use App\Models\RbacAuditLog;
use App\Models\StaffRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StaffController extends Controller
{
    // Non-farmer / non-external roles treated as "staff"
    private const EXTERNAL_ROLES = ['farmer', 'general-user', 'cooperative', 'government-agency', 'ngo', 'research-institution', 'investor'];

    public function index(Request $request)
    {
        $query = User::whereNotIn('role', self::EXTERNAL_ROLES)
                     ->with('staffRoles');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'ilike', "%{$request->search}%")
                  ->orWhere('last_name',  'ilike', "%{$request->search}%")
                  ->orWhere('email',      'ilike', "%{$request->search}%");
            });
        }
        if ($request->role)                 $query->where('role', $request->role);
        if ($request->status === 'active')  $query->where('is_active', true);
        if ($request->status === 'inactive')$query->where('is_active', false);
        if ($request->department)           $query->where('department', $request->department);

        $staff    = $query->latest()->paginate(20)->withQueryString();
        $roles    = StaffRole::where('is_active', true)->orderBy('name')->get();
        $departments = User::whereNotIn('role', self::EXTERNAL_ROLES)
                           ->whereNotNull('department')
                           ->distinct()->pluck('department')->sort()->values();

        $stats = [
            'total'    => User::whereNotIn('role', self::EXTERNAL_ROLES)->count(),
            'active'   => User::whereNotIn('role', self::EXTERNAL_ROLES)->where('is_active', true)->count(),
            'inactive' => User::whereNotIn('role', self::EXTERNAL_ROLES)->where('is_active', false)->count(),
            'with_custom_role' => User::whereNotIn('role', self::EXTERNAL_ROLES)->has('staffRoles')->count(),
        ];

        return view('ceo.staff.index', compact('staff', 'roles', 'departments', 'stats'));
    }

    public function create()
    {
        $staffRoles = StaffRole::where('is_active', true)->orderBy('name')->get();
        $systemRoles = $this->systemRoleOptions();
        return view('ceo.staff.create', compact('staffRoles', 'systemRoles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20',
            'role'          => 'required|string',
            'department'    => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'staff_role_ids'=> 'nullable|array',
            'staff_role_ids.*' => 'exists:staff_roles,id',
        ]);

        $user = User::create([
            'first_name'           => $data['first_name'],
            'middle_name'          => $data['middle_name'] ?? null,
            'last_name'            => $data['last_name'],
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'password'             => Hash::make('Welcome@123'),
            'role'                 => $data['role'],
            'department'           => $data['department'] ?? null,
            'state'                => $data['state'] ?? null,
            'is_active'            => true,
            'is_verified'          => true,
            'force_password_reset' => true,
            'language'             => 'en',
        ]);

        if (! empty($data['staff_role_ids'])) {
            $pivot = [];
            foreach ($data['staff_role_ids'] as $roleId) {
                $pivot[$roleId] = ['assigned_by' => auth()->id(), 'assigned_at' => now()];
            }
            $user->staffRoles()->sync($pivot);
        }

        RbacAuditLog::record('staff_created', 'User', $user->id, $user->name, null, [
            'role'        => $user->role,
            'email'       => $user->email,
            'department'  => $user->department,
            'staff_roles' => $data['staff_role_ids'] ?? [],
        ]);

        // Send welcome email with login credentials directly to the staff member
        try {
            Mail::to($user->email)->send(new StaffWelcomeMail($user, 'Welcome@123', isReset: false));
        } catch (\Throwable $e) {
            Log::warning('StaffWelcomeMail failed to send', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('ceo.staff.show', $user)
                         ->with('success', "Staff account for {$user->name} created. Login credentials have been emailed to {$user->email}. They must change their password on first login.");
    }

    public function show(User $user)
    {
        $user->load('staffRoles');
        $staffRoles  = StaffRole::where('is_active', true)->orderBy('name')->get();
        $systemRoles = $this->systemRoleOptions();
        $auditLogs   = RbacAuditLog::where(function ($q) use ($user) {
                            $q->where('actor_id', $user->id)
                              ->orWhere(fn ($q2) => $q2->where('target_type', 'User')->where('target_id', $user->id));
                        })->with('actor')->latest()->take(30)->get();

        return view('ceo.staff.show', compact('user', 'staffRoles', 'systemRoles', 'auditLogs'));
    }

    public function edit(User $user)
    {
        $user->load('staffRoles');
        $staffRoles  = StaffRole::where('is_active', true)->orderBy('name')->get();
        $systemRoles = $this->systemRoleOptions();
        return view('ceo.staff.edit', compact('user', 'staffRoles', 'systemRoles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'phone'         => 'nullable|string|max:20',
            'role'          => 'required|string',
            'department'    => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'staff_role_ids'=> 'nullable|array',
            'staff_role_ids.*' => 'exists:staff_roles,id',
        ]);

        $before = $user->only(['first_name', 'last_name', 'email', 'role', 'department']);

        $user->update([
            'first_name'  => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name'   => $data['last_name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'role'        => $data['role'],
            'department'  => $data['department'] ?? null,
            'state'       => $data['state'] ?? null,
        ]);

        $pivot = collect($data['staff_role_ids'] ?? [])
            ->mapWithKeys(fn ($id) => [$id => ['assigned_by' => auth()->id(), 'assigned_at' => now()]])
            ->toArray();
        $user->staffRoles()->sync($pivot);

        $after = $user->fresh()->only(['first_name', 'last_name', 'email', 'role', 'department']);
        RbacAuditLog::record('staff_updated', 'User', $user->id, $user->name, $before, $after);

        return redirect()->route('ceo.staff.show', $user)->with('success', 'Staff profile updated.');
    }

    public function assignRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'staff_role_ids'   => 'required|array|min:1',
            'staff_role_ids.*' => 'exists:staff_roles,id',
        ]);

        $before = $user->staffRoles->pluck('name')->toArray();

        $pivot = collect($data['staff_role_ids'])
            ->mapWithKeys(fn ($id) => [$id => ['assigned_by' => auth()->id(), 'assigned_at' => now()]])
            ->toArray();
        $user->staffRoles()->sync($pivot);

        $after = $user->fresh()->staffRoles->pluck('name')->toArray();
        RbacAuditLog::record('role_assigned', 'User', $user->id, $user->name, ['roles' => $before], ['roles' => $after]);

        return back()->with('success', 'Role assignments updated for ' . $user->name . '.');
    }

    public function toggle(User $user)
    {
        // Protect the CEO account from being suspended
        if ($user->role === 'ceo') {
            return back()->with('error', 'The CEO account cannot be suspended.');
        }

        $before = ['is_active' => $user->is_active];
        $user->update(['is_active' => ! $user->is_active]);
        $after  = ['is_active' => $user->is_active];
        $action = $user->is_active ? 'staff_activated' : 'staff_suspended';

        RbacAuditLog::record($action, 'User', $user->id, $user->name, $before, $after);

        $status = $user->is_active ? 'activated' : 'suspended';
        return back()->with('success', "{$user->name} has been {$status}.");
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password'             => Hash::make('Welcome@123'),
            'force_password_reset' => true,
        ]);

        RbacAuditLog::record('password_reset', 'User', $user->id, $user->name, null, ['force_password_reset' => true]);

        // Email the new temporary password directly to the staff member
        try {
            Mail::to($user->email)->send(new StaffWelcomeMail($user, 'Welcome@123', isReset: true));
        } catch (\Throwable $e) {
            Log::warning('StaffWelcomeMail (reset) failed to send', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', "Password reset for {$user->name}. New temporary credentials have been emailed to {$user->email}. They must change their password on next login.");
    }

    public function removeRole(Request $request, User $user)
    {
        $data = $request->validate(['staff_role_id' => 'required|exists:staff_roles,id']);

        $roleName = StaffRole::find($data['staff_role_id'])?->name ?? 'Unknown';
        $user->staffRoles()->detach($data['staff_role_id']);

        RbacAuditLog::record('role_removed', 'User', $user->id, $user->name, ['role' => $roleName], null);

        return back()->with('success', "Role \"{$roleName}\" removed from {$user->name}.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function systemRoleOptions(): array
    {
        return [
            'admin'              => 'Administrator',
            'vet'                => 'Veterinarian',
            'agronomist'         => 'Agronomist',
            'agro-dealer'        => 'Agro Dealer',
            'equipment-dealer'   => 'Equipment Dealer',
            'agribusiness-owner' => 'Agribusiness Owner',
            'input-supplier'     => 'Input Supplier',
            'logistics-provider' => 'Logistics Provider',
            'extension-officer'  => 'Extension Officer',
            'field-officer'      => 'Field Officer',
            'data-analyst'       => 'Data Analyst',
            'm-e-officer'        => 'M&E Officer',
            'customer-support'   => 'Customer Support',
            'hr'                 => 'HR Officer',
            'finance'            => 'Finance Officer',
            'operations'         => 'Operations Manager',
        ];
    }
}
