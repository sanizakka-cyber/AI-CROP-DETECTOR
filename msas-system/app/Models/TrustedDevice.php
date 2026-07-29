<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'token_hash', 'device_name', 'browser', 'platform',
        'ip_address', 'last_used_at', 'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function getDeviceLabelAttribute(): string
    {
        $parts = array_filter([$this->browser, $this->platform]);
        return implode(' on ', $parts) ?: 'Unknown device';
    }
}
