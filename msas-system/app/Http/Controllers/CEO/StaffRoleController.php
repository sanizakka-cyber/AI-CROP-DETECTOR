<?php

namespace App\Http\Controllers\CEO;

use App\Http\Controllers\Controller;
use App\Models\RbacAuditLog;
use App\Models\StaffRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaffRoleController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffRole::withCount('users');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',       'ilike', "%{$request->search}%")
                  ->orWhere('department','ilike', "%{$request->search}%");
            });
        }
        if ($request->status === 'active')   $query->where('is_active', true);
        if ($request->status === 'inactive') $query->where('is_active', false);

        $roles = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'    => StaffRole::count(),
            'active'   => StaffRole::where('is_active', true)->count(),
            'inactive' => StaffRole::where('is_active', false)->count(),
            'assigned' => StaffRole::has('users')->count(),
        ];

        return view('ceo.staff-roles.index', compact('roles', 'stats'));
    }

    public function create()
    {
        $modules   = StaffRole::modules();
        $abilities = StaffRole::abilities();
        return view('ceo.staff-roles.create', compact('modules', 'abilities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100|unique:staff_roles,name',
            'department'       => 'nullable|string|max:100',
            'description'      => 'nullable|string|max:1000',
            'responsibilities' => 'nullable|string|max:2000',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'array',
            'permissions.*.*'  => 'string|in:' . implode(',', array_keys(StaffRole::abilities())),
        ]);

        $role = StaffRole::create([
            'name'             => $data['name'],
            'slug'             => Str::slug($data['name']),
            'department'       => $data['department'] ?? null,
            'description'      => $data['description'] ?? null,
            'responsibilities' => $data['responsibilities'] ?? null,
            'permissions'      => $data['permissions'] ?? [],
            'is_active'        => true,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        RbacAuditLog::record('role_created', 'StaffRole', $role->id, $role->name, null, $role->toArray());

        return redirect()->route('ceo.staff-roles.show', $role)
                         ->with('success', "Role \"{$role->name}\" created successfully.");
    }

    public function show(StaffRole $staffRole)
    {
        $staffRole->loadCount('users');
        $staffRole->load('creator', 'updater');
        $assignedUsers = $staffRole->users()->with('staffRoles')->latest('staff_role_assignments.created_at')->paginate(15);
        $modules       = StaffRole::modules();
        $abilities     = StaffRole::abilities();
        $auditLogs     = RbacAuditLog::where('target_type', 'StaffRole')
                            ->where('target_id', $staffRole->id)
                            ->with('actor')
                            ->latest()
                            ->take(20)
                            ->get();

        return view('ceo.staff-roles.show', compact('staffRole', 'assignedUsers', 'modules', 'abilities', 'auditLogs'));
    }

    public function edit(StaffRole $staffRole)
    {
        $modules   = StaffRole::modules();
        $abilities = StaffRole::abilities();
        return view('ceo.staff-roles.edit', compact('staffRole', 'modules', 'abilities'));
    }

    public function update(Request $request, StaffRole $staffRole)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100|unique:staff_roles,name,' . $staffRole->id,
            'department'       => 'nullable|string|max:100',
            'description'      => 'nullable|string|max:1000',
            'responsibilities' => 'nullable|string|max:2000',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'array',
            'permissions.*.*'  => 'string|in:' . implode(',', array_keys(StaffRole::abilities())),
        ]);

        $before = $staffRole->toArray();

        $staffRole->update([
            'name'             => $data['name'],
            'department'       => $data['department'] ?? null,
            'description'      => $data['description'] ?? null,
            'responsibilities' => $data['responsibilities'] ?? null,
            'permissions'      => $data['permissions'] ?? [],
            'updated_by'       => auth()->id(),
        ]);

        RbacAuditLog::record('role_updated', 'StaffRole', $staffRole->id, $staffRole->name, $before, $staffRole->fresh()->toArray());

        return redirect()->route('ceo.staff-roles.show', $staffRole)
                         ->with('success', "Role \"{$staffRole->name}\" updated.");
    }

    public function toggleActive(StaffRole $staffRole)
    {
        $before = ['is_active' => $staffRole->is_active];
        $staffRole->update([
            'is_active'  => ! $staffRole->is_active,
            'updated_by' => auth()->id(),
        ]);
        $after  = ['is_active' => $staffRole->is_active];
        $action = $staffRole->is_active ? 'role_activated' : 'role_deactivated';

        RbacAuditLog::record($action, 'StaffRole', $staffRole->id, $staffRole->name, $before, $after);

        $status = $staffRole->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Role \"{$staffRole->name}\" {$status}.");
    }

    public function destroy(StaffRole $staffRole)
    {
        if ($staffRole->users()->exists()) {
            return back()->with('error', "Cannot delete \"{$staffRole->name}\" — it is still assigned to " . $staffRole->users()->count() . " staff member(s). Remove assignments first.");
        }

        RbacAuditLog::record('role_deleted', 'StaffRole', $staffRole->id, $staffRole->name, $staffRole->toArray(), null);
        $staffRole->delete();

        return redirect()->route('ceo.staff-roles.index')
                         ->with('success', "Role deleted.");
    }
}
