<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'buyer_id', 'dealer_id', 'status', 'payment_status',
        'payment_method', 'payment_channel', 'payment_reference', 'subtotal', 'discount', 'tax', 'total',
        'delivery_address', 'delivery_notes', 'confirmed_at', 'delivered_at',
        'payout_status', 'payout_amount', 'payout_requested_at', 'payout_paid_at',
        'payout_reference', 'payout_notes',
        // rider assignment
        'rider_id', 'assigned_by', 'rider_status',
        'rider_assigned_at', 'rider_accepted_at', 'in_transit_at',
        'completed_at', 'returned_at', 'buyer_confirmed_at',
        'rejection_reason', 'admin_notes',
    ];

    protected $casts = [
        'subtotal'            => 'float',
        'discount'            => 'float',
        'tax'                 => 'float',
        'total'               => 'float',
        'payout_amount'       => 'float',
        'confirmed_at'        => 'datetime',
        'delivered_at'        => 'datetime',
        'payout_requested_at' => 'datetime',
        'payout_paid_at'      => 'datetime',
        'rider_assigned_at'   => 'datetime',
        'rider_accepted_at'   => 'datetime',
        'in_transit_at'       => 'datetime',
        'completed_at'        => 'datetime',
        'returned_at'         => 'datetime',
        'buyer_confirmed_at'  => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────────

    public function buyer()      { return $this->belongsTo(User::class, 'buyer_id'); }
    public function dealer()     { return $this->belongsTo(User::class, 'dealer_id'); }
    public function rider()      { return $this->belongsTo(User::class, 'rider_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
    public function items()      { return $this->hasMany(OrderItem::class); }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    public function isPayoutEligible(): bool
    {
        return $this->status === 'delivered'
            && $this->payment_status === 'paid'
            && $this->payout_status === null;
    }

    public function isAssignable(): bool
    {
        return in_array($this->status, ['confirmed', 'processing'])
            && !in_array($this->rider_status, ['accepted', 'in_transit']);
    }

    public function riderStatusBadge(): array
    {
        return match ($this->rider_status ?? 'unassigned') {
            'assigned'   => ['label' => 'Rider Assigned',  'class' => 'bg-blue-100 text-blue-800'],
            'accepted'   => ['label' => 'Rider Accepted',  'class' => 'bg-indigo-100 text-indigo-800'],
            'declined'   => ['label' => 'Rider Declined',  'class' => 'bg-red-100 text-red-800'],
            'in_transit' => ['label' => 'In Transit',      'class' => 'bg-purple-100 text-purple-800'],
            'completed'  => ['label' => 'Completed',       'class' => 'bg-green-100 text-green-800'],
            'returned'   => ['label' => 'Returned',        'class' => 'bg-amber-100 text-amber-800'],
            default      => ['label' => 'Unassigned',      'class' => 'bg-slate-100 text-slate-600'],
        };
    }

    public static function generateNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
