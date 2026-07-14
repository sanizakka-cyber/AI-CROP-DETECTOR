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
    ];

    protected $casts = [
        'subtotal'     => 'float',
        'discount'     => 'float',
        'tax'          => 'float',
        'total'        => 'float',
        'confirmed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function buyer()   { return $this->belongsTo(User::class, 'buyer_id'); }
    public function dealer()  { return $this->belongsTo(User::class, 'dealer_id'); }
    public function items()   { return $this->hasMany(OrderItem::class); }

    public static function generateNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
