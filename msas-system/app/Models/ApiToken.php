<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A mobile bearer token: one row per signed-in device. Only the SHA-256 hash is stored. */
class ApiToken extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'name', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
