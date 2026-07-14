<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_sku', 'unit',
        'quantity', 'unit_price', 'total',
    ];

    protected $casts = ['unit_price' => 'float', 'total' => 'float'];

    public function order()   { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
