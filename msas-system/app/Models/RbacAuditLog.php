<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RbacAuditLog extends Model
{
    protected $fillable = [
        'actor_id', 'action', 'target_type', 'target_id',
        'target_label', 'before', 'after', 'ip_address',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(
        string  $action,
        string  $targetType,
        int     $targetId,
        ?string $targetLabel = null,
        ?array  $before = null,
        ?array  $after  = null,
    ): void {
        try {
            static::create([
                'actor_id'     => auth()->id(),
                'action'       => $action,
                'target_type'  => $targetType,
                'target_id'    => $targetId,
                'target_label' => $targetLabel,
                'before'       => $before,
                'after'        => $after,
                'ip_address'   => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Non-fatal: audit log failure must never block the main operation.
        }
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'role_created'    => 'Role Created',
            'role_updated'    => 'Role Updated',
            'role_activated'  => 'Role Activated',
            'role_deactivated'=> 'Role Deactivated',
            'role_deleted'    => 'Role Deleted',
            'role_assigned'   => 'Role Assigned',
            'role_removed'    => 'Role Removed',
            'staff_created'   => 'Staff Created',
            'staff_updated'   => 'Staff Updated',
            'staff_activated' => 'Staff Activated',
            'staff_suspended' => 'Staff Suspended',
            'password_reset'  => 'Password Reset',
            default           => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match (true) {
            str_contains($this->action, 'delete')    => 'red',
            str_contains($this->action, 'suspend')   => 'orange',
            str_contains($this->action, 'deactivate')=> 'orange',
            str_contains($this->action, 'create')    => 'green',
            str_contains($this->action, 'activate')  => 'green',
            str_contains($this->action, 'reset')     => 'yellow',
            default                                   => 'blue',
        };
    }
}
