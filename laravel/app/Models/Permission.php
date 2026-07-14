<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $fillable = ['name', 'category', 'description'];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    // ── Convenience: get all permission names for a given role ─────────────────
    public static function forRole(string $role): array
    {
        if ($role === 'ceo') {
            return static::pluck('name')->toArray();
        }

        return static::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $role)
            ->pluck('permissions.name')
            ->toArray();
    }
}
