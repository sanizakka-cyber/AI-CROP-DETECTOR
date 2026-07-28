<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['from_user_id', 'to_user_id', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    // Count unread messages sent TO $userId
    public static function unreadCountFor(int $userId): int
    {
        return static::where('to_user_id', $userId)->whereNull('read_at')->count();
    }
}
