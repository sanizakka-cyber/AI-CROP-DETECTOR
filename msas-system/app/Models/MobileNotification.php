<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileNotification extends Model
{
    protected $table = 'mobile_notifications';

    protected $fillable = [
        'user_id', 'title', 'body', 'type', 'data', 'icon', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Create and optionally push a notification to a user.
     */
    public static function send(int $userId, string $title, string $body, string $type = 'system', array $data = [], string $icon = '🔔'): self
    {
        $notif = self::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
            'icon'    => $icon,
        ]);

        // Fire-and-forget Expo push notification
        $user = User::find($userId);
        if ($user && $user->expo_push_token) {
            self::pushViaExpo($user->expo_push_token, $title, $body, $data);
        }

        return $notif;
    }

    private static function pushViaExpo(string $token, string $title, string $body, array $data = []): void
    {
        try {
            \Illuminate\Support\Facades\Http::post('https://exp.host/--/api/v2/push/send', [
                'to'    => $token,
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
                'sound' => 'default',
            ]);
        } catch (\Exception) {
            // Non-critical — push delivery failure should not break app flow
        }
    }
}
