<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_after',
        'reference', 'description', 'status', 'metadata', 'performed_by',
    ];

    protected $casts = [
        'amount'       => 'float',
        'balance_after'=> 'float',
        'metadata'     => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isCredit(): bool
    {
        return in_array($this->type, ['credit', 'refund', 'release']);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['debit', 'hold']);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'completed' => '<span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Completed</span>',
            'pending'   => '<span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Pending</span>',
            'failed'    => '<span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Failed</span>',
            'cancelled' => '<span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Cancelled</span>',
            default     => e($this->status),
        };
    }
}
