<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission_id'];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
