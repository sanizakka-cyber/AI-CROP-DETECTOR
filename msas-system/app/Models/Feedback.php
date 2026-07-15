<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_approved' => 'boolean',
    ];
}
