<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRequest extends Model
{
    protected $fillable = [
        'ref_number', 'logistics_provider_id', 'order_id', 'vehicle_id', 'driver_id',
        'requester_id', 'status', 'pickup_address', 'delivery_address',
        'contact_name', 'contact_phone', 'cargo_weight_kg', 'cargo_description',
        'delivery_fee', 'notes', 'assigned_at', 'picked_up_at', 'delivered_at',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logistics_provider_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(LogisticsVehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(LogisticsDriver::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public static function generateRef(): string
    {
        return 'DEL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
