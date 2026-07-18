<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'user_type', 'reference', 'amount', 'currency',
        'module', 'module_id', 'description', 'metadata',
        'status', 'transaction_id', 'payment_method', 'channel',
        'gateway_response', 'verification_status', 'verified_at',
        'paid_at', 'receipt_number',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'amount'      => 'decimal:2',
        'verified_at' => 'datetime',
        'paid_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function formattedAmount(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    public static function generateReference(string $prefix = 'MSAS'): string
    {
        return $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(12));
    }

    public static function generateReceiptNumber(): string
    {
        return 'RCP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
    }

    // Scope helpers
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'cancelled']);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
