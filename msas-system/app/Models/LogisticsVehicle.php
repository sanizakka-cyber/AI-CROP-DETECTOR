<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsVehicle extends Model
{
    protected $fillable = [
        'user_id', 'reg_number', 'make', 'model', 'year',
        'vehicle_type', 'capacity_kg', 'status', 'notes',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'vehicle_id');
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->vehicle_type) {
            'truck'        => '🚛',
            'van'          => '🚐',
            'motorcycle'   => '🏍',
            'pickup'       => '🛻',
            'refrigerated' => '🧊',
            default        => '🚗',
        };
    }
}
