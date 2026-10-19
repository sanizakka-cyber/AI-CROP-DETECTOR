<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A scan that could not complete (App\Support\Scan\ScanErrorCode). */
class ScanError extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'reference', 'code', 'user_id', 'user_role', 'channel', 'scan_type', 'service',
        'http_status', 'upstream_status', 'detail', 'context',
    ];

    protected $casts = ['context' => 'array', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** SCN- plus 5 characters without look-alikes (no 0/O, 1/I/L). */
    public static function newReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        do {
            $ref = 'SCN-';
            for ($i = 0; $i < 5; $i++) {
                $ref .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('reference', $ref)->exists());
        return $ref;
    }
}
