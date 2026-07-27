<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'locked_balance', 'currency', 'is_active',
    ];

    protected $casts = [
        'balance'        => 'float',
        'locked_balance' => 'float',
        'is_active'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function getAvailableBalanceAttribute(): float
    {
        return max(0, $this->balance - $this->locked_balance);
    }

    public function getTotalCreditedAttribute(): float
    {
        return (float) $this->transactions()
            ->whereIn('type', ['credit', 'refund'])
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getTotalWithdrawnAttribute(): float
    {
        return (float) $this->transactions()
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');
    }
}
