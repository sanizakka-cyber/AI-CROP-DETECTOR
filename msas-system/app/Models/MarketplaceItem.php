<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
