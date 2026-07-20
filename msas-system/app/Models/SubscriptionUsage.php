<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    protected $table = 'subscription_usage';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function track(int $userId, string $featureKey, int $by = 1): void
    {
        $period = now()->format('Y-m');
        static::updateOrCreate(
            ['user_id' => $userId, 'feature_key' => $featureKey, 'period' => $period],
            ['count' => 0]
        );
        static::where('user_id', $userId)
            ->where('feature_key', $featureKey)
            ->where('period', $period)
            ->increment('count', $by);
    }

    public static function getCount(int $userId, string $featureKey, ?string $period = null): int
    {
        $period ??= now()->format('Y-m');
        return static::where('user_id', $userId)
            ->where('feature_key', $featureKey)
            ->where('period', $period)
            ->value('count') ?? 0;
    }
}
