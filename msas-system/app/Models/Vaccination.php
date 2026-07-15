<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    protected $guarded = [];

    protected $casts = [
        'given_date' => 'date',
        'next_due'   => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
