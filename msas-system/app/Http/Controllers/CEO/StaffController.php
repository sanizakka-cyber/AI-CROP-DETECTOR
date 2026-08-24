<?php

namespace App\Http\Controllers\CEO;

use App\Http\Controllers\Controller;
use App\Mail\StaffWelcomeMail;
use App\Models\RbacAuditLog;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    // Non-farmer / non-external roles treated as "staff"
    private const EXTERNAL_ROLES = ['farmer', 'general-user', 'cooperative', 'government-agency', 'ngo', 'research-institution', 'investor'];

    // Controlled department list — was previously free text with no
    // validation, letting the same department accumulate inconsistent
    // spellings the same way roles did (see App\Support\Roles's doc comment).
    private const DEPARTMENTS = [
        'Finance', 'Human Resources', 'Operations', 'Administration',
        'Monitoring & Evaluation', 'Information Technology',
        'Agriculture/Agribusiness', 'Veterinary/Livestock', 'Management',
    ];

    // Soft default only — a role suggests a department, it never forces
    // one. The CEO can always pick a different department; this just saves
    // a click for the common case and isn't a validation rule.
    private const ROLE_DEPARTMENT_SUGGESTION = [
        'finance'            => 'Finance',
        'hr'                 => 'Human Resources',
        'operations'         => 'Operations',
        'm-e-officer'        => 'Monitoring & Evaluation',
        'data-analyst'       => 'Monitoring & Evaluation',
        'vet'                => 'Veterinary/Livestock',
        'agronomist'         => 'Agriculture/Agribusiness',
        'extension-officer'  => 'Agriculture/Agribusiness',
        'field-officer'      => 'Agriculture/Agribusiness',
        'admin'              => 'Administration',
        'ceo'                => 'Management',
    ];

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
        $departmentOptions = self::DEPARTMENTS;
        return view('ceo.staff.create', compact('staffRoles', 'systemRoles', 'departmentOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20',
            'role'          => ['required', 'string', 'in:' . implode(',', array_keys($this->systemRoleOptions()))],
            'department'    => ['nullable', 'string', 'in:' . implode(',', self::DEPARTMENTS)],
            'state'         => ['nullable', 'string', 'in:' . implode(',', array_column(\App\Data\NigeriaLocations::states(), 'name'))],
            'lga'           => ['nullable', 'string', 'max:100'],
            'staff_role_ids'=> 'nullable|array',
            'staff_role_ids.*' => 'exists:staff_roles,id',
        ]);

        $user = User::create([
            'first_name'           => $data['first_name'],
            'middle_name'          => $data['middle_name'] ?? null,
            'last_name'            => $data['last_name'],
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'password'             => Hash::make(Str::random(32)),
            'role'                 => Roles::canonical($data['role']),
            'department'           => $data['department'] ?? null,
            'state'                => $data['state'] ?? null,
            'lga'                  => $data['lga'] ?? null,
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

        $emailSent = $this->sendStaffWelcomeEmail($user, isReset: false);

        // 'warning' is not a flash key any view actually renders (only
        // 'success'/'error' are) — using it here would silently drop this
        // message exactly the way the underlying bug this fixes did.
        return redirect()->route('ceo.staff.show', $user)->with(
            $emailSent ? 'success' : 'error',
            $emailSent
                ? "Staff account for {$user->name} created. A one-time password-set link has been emailed to {$user->email}."
                : "Staff account for {$user->name} was created, but the welcome email could not be sent. Use \"Reset Password\" from their profile to try sending the setup link again."
        );
    }

    /**
     * Bootstraps the same session state the interactive "forgot password"
     * OTP flow produces, via a signed link — so a new/reset staff account
     * gets a real, working one-click path to NewPasswordController's
     * existing form, instead of a Password::broker() token this app's
     * reset-password page was never wired to accept (that page reads
     * session('reset_token')/session('reset_user_id'), set only by
     * OtpVerificationController — a bare token+email in a URL was never
     * something it checked, so every previously "emailed" link 404'd on
     * session-expired even when the email itself had sent successfully).
     * Never emails a plaintext password — the user still chooses their own.
     */
    private function sendStaffWelcomeEmail(User $user, bool $isReset): bool
    {
        try {
            $link = URL::temporarySignedRoute('staff.first-login', now()->addDays(7), ['user' => $user->id]);
            Mail::to($user->email)->send(new StaffWelcomeMail($user, $link, isReset: $isReset));
            return true;
        } catch (\Throwable $e) {
            Log::warning('StaffWelcomeMail failed to send', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return false;
        }
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
        $departmentOptions = self::DEPARTMENTS;
        return view('ceo.staff.edit', compact('user', 'staffRoles', 'systemRoles', 'departmentOptions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'middle_name'   => 'nullable|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'phone'         => 'nullable|string|max:20',
            'role'          => ['required', 'string', 'in:' . implode(',', array_keys($this->systemRoleOptions()))],
            'department'    => ['nullable', 'string', 'in:' . implode(',', self::DEPARTMENTS)],
            'state'         => ['nullable', 'string', 'in:' . implode(',', array_column(\App\Data\NigeriaLocations::states(), 'name'))],
            'lga'           => ['nullable', 'string', 'max:100'],
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
            'role'        => $user->role === 'ceo' ? 'ceo' : Roles::canonical($data['role']),
            'department'  => $data['department'] ?? null,
            'state'       => $data['state'] ?? null,
            'lga'         => $data['lga'] ?? null,
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
            'password'             => Hash::make(Str::random(32)),
            'force_password_reset' => true,
        ]);

        RbacAuditLog::record('password_reset', 'User', $user->id, $user->name, null, ['force_password_reset' => true]);

        $emailSent = $this->sendStaffWelcomeEmail($user, isReset: true);

        return back()->with(
            $emailSent ? 'success' : 'error',
            $emailSent
                ? "Password reset for {$user->name}. A one-time password-set link has been emailed to {$user->email}."
                : "Password reset for {$user->name}, but the email could not be sent. Please try \"Reset Password\" again shortly."
        );
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
