<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EggProduction extends Model
{
    // total_value is a PostgreSQL GENERATED ALWAYS AS (quantity * unit_price) STORED column
    // and must never be passed in INSERT/UPDATE statements.
    protected $guarded = ['total_value'];

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
