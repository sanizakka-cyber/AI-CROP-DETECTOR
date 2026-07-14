<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'cancelled_at'  => 'datetime',
        'upgraded_at'   => 'datetime',
        'auto_renew'    => 'boolean',
        'amount_paid'   => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    // ── Status Checks ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']) && $this->endsAt()->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->endsAt()->isPast() && $this->status !== 'cancelled');
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function endsAt(): Carbon
    {
        if ($this->status === 'trial') {
            return $this->trial_ends_at ?? now()->subDay();
        }
        return $this->ends_at ?? now()->subDay();
    }

    public function daysRemaining(): int
    {
        $end = $this->endsAt();
        return max(0, (int) now()->diffInDays($end, false));
    }

    // ── Plan Hierarchy ──────────────────────────────────────────────────────

    public function planLevel(): int
    {
        return config("subscription.plans.{$this->plan}.plan_level", 0);
    }

    public function planConfig(): array
    {
        return config("subscription.plans.{$this->plan}", []);
    }

    public function planName(): string
    {
        return config("subscription.plans.{$this->plan}.name", ucfirst($this->plan));
    }

    // ── Feature & Limit Access ──────────────────────────────────────────────

    public function hasFeature(string $feature): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        $features = config("subscription.plans.{$this->plan}.features", []);
        return in_array($feature, $features);
    }

    public function getLimit(string $key): int
    {
        return config("subscription.plans.{$this->plan}.limits.{$key}", 0);
    }

    public function hasReachedLimit(string $limitKey): bool
    {
        $limit = $this->getLimit($limitKey);
        if ($limit === -1) return false; // unlimited

        $period = now()->format('Y-m');
        $usage = SubscriptionUsage::where('user_id', $this->user_id)
            ->where('feature_key', $limitKey)
            ->where('period', $period)
            ->value('count') ?? 0;

        return $usage >= $limit;
    }

    public function currentUsage(string $limitKey): int
    {
        $period = now()->format('Y-m');
        return SubscriptionUsage::where('user_id', $this->user_id)
            ->where('feature_key', $limitKey)
            ->where('period', $period)
            ->value('count') ?? 0;
    }

    // ── Pricing ────────────────────────────────────────────────────────────

    public static function monthlyPrice(string $plan): int
    {
        return config("subscription.plans.{$plan}.price.monthly", 0);
    }

    public static function yearlyPrice(string $plan): int
    {
        return config("subscription.plans.{$plan}.price.yearly", 0);
    }
}
