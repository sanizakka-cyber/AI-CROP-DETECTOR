<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InviteCode extends Model
{
    protected $fillable = ['code','created_by','plan','max_uses','used_count','expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->used_count < $this->max_uses
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public static function generate(): string
    {
        do {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}