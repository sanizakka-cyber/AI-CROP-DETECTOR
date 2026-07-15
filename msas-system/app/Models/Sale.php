<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sale_date'  => 'date',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
