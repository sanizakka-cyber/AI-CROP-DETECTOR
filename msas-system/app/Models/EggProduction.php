<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EggProduction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'production_date' => 'date',
        'unit_price'      => 'decimal:2',
        'total_value'     => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
