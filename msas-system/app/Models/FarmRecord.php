<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'planting_date' => 'date',
        'harvest_date'  => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
