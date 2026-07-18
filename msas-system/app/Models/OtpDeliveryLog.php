<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpDeliveryLog extends Model
{
    protected $table = 'otp_delivery_logs';

    protected $fillable = [
        'user_id', 'identifier_hint', 'type', 'channel',
        'provider', 'delivered', 'message_id', 'error',
        'verification_status', 'verified_at',
    ];

    protected $casts = [
        'delivered'   => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience factory used by OtpService / controllers.
     */
    public static function record(
        ?int    $userId,
        string  $identifierHint,
        string  $type,
        string  $channel,
        bool    $delivered,
        ?string $provider    = null,
        ?string $messageId   = null,
        ?string $error       = null,
    ): self {
        return self::create([
            'user_id'             => $userId,
            'identifier_hint'     => $identifierHint,
            'type'                => $type,
            'channel'             => $channel,
            'provider'            => $provider,
            'delivered'           => $delivered,
            'message_id'          => $messageId,
            'error'               => $error,
            'verification_status' => 'pending',
        ]);
    }
}
