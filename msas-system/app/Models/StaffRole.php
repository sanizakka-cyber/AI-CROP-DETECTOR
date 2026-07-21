<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StaffRole extends Model
{
    protected $fillable = [
        'name', 'slug', 'department', 'description', 'responsibilities',
        'permissions', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active'   => 'boolean',
    ];

    // ── Modules available for permission assignment ────────────────────────────

    public static function modules(): array
    {
        return [
            'dashboard'     => 'Dashboard',
            'users'         => 'User Management',
            'ai_scan'       => 'AI Smart Scan',
            'marketplace'   => 'Marketplace',
            'orders'        => 'Orders',
            'payments'      => 'Payments',
            'subscriptions' => 'Subscriptions',
            'reports'       => 'Reports',
            'analytics'     => 'Analytics',
            'notifications' => 'Notifications',
            'consultations' => 'Consultations',
            'settings'      => 'Settings',
            'audit_logs'    => 'Audit Logs',
            'hr'            => 'HR Management',
            'finance'       => 'Finance',
            'field_ops'     => 'Field Operations',
            'support'       => 'Customer Support',
            'livestock'     => 'Livestock & Animals',
        ];
    }

    // ── Granular abilities ────────────────────────────────────────────────────

    public static function abilities(): array
    {
        return [
            'view'        => 'View',
            'create'      => 'Create',
            'edit'        => 'Edit',
            'delete'      => 'Delete',
            'approve'     => 'Approve',
            'reject'      => 'Reject',
            'export'      => 'Export',
            'manage'      => 'Manage',
            'full_access' => 'Full Access',
        ];
    }

    // ── Permission check ──────────────────────────────────────────────────────

    public function hasPermission(string $module, string $ability): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $perms       = $this->permissions ?? [];
        $modulePerms = $perms[$module] ?? [];
        return in_array('full_access', $modulePerms, true)
            || in_array($ability, $modulePerms, true);
    }

    public function hasAnyPermission(string $module): bool
    {
        $perms = $this->permissions ?? [];
        return ! empty($perms[$module] ?? []);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function users()
    {
        return $this->belongsToMany(User::class, 'staff_role_assignments')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Slug auto-generation ──────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $role) {
            if (empty($role->slug)) {
                $role->slug = self::uniqueSlug($role->name);
            }
        });
    }

    private static function uniqueSlug(string $name): string
    {
        $slug  = Str::slug($name);
        $count = self::where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getPermissionSummaryAttribute(): string
    {
        $perms   = $this->permissions ?? [];
        $modules = array_keys(array_filter($perms));
        $count   = count($modules);
        return $count === 0 ? 'No permissions' : "{$count} module" . ($count !== 1 ? 's' : '');
    }

    public function activeUserCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }
}
