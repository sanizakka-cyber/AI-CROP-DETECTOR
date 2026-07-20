<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsDriver extends Model
{
    protected $fillable = [
        'user_id', 'first_name', 'last_name',
        'license_number', 'phone', 'status', 'notes',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'driver_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
