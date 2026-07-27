<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function getOrCreate(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'locked_balance' => 0, 'currency' => 'NGN', 'is_active' => true]
        );
    }

    public function credit(User $user, float $amount, string $description, array $metadata = [], ?string $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata, $reference) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'currency' => 'NGN', 'is_active' => true]
            );

            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => 'credit',
                'amount'        => $amount,
                'balance_after' => $wallet->balance,
                'reference'     => $reference ?? 'CR-' . strtoupper(Str::random(12)),
                'description'   => $description,
                'status'        => 'completed',
                'metadata'      => $metadata,
                'performed_by'  => auth()->id(),
            ]);
        });
    }

    public function debit(User $user, float $amount, string $description, array $metadata = [], ?string $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata, $reference) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => 'debit',
                'amount'        => $amount,
                'balance_after' => $wallet->balance,
                'reference'     => $reference ?? 'DB-' . strtoupper(Str::random(12)),
                'description'   => $description,
                'status'        => 'completed',
                'metadata'      => $metadata,
                'performed_by'  => auth()->id(),
            ]);
        });
    }

    /**
     * Lock funds for a pending withdrawal (deduct from available, keep in locked).
     */
    public function requestWithdrawal(User $user, float $amount, array $bankDetails): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $bankDetails) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $amount) {
                throw new \RuntimeException('Insufficient balance for withdrawal.');
            }

            $wallet->increment('locked_balance', $amount);
            $wallet->refresh();

            $reference = 'WD-' . strtoupper(Str::random(12));

            return WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => 'hold',
                'amount'        => $amount,
                'balance_after' => $wallet->balance,
                'reference'     => $reference,
                'description'   => 'Withdrawal request pending approval',
                'status'        => 'pending',
                'metadata'      => array_merge($bankDetails, ['requested_at' => now()->toISOString()]),
                'performed_by'  => $user->id,
            ]);
        });
    }

    /**
     * Admin approves withdrawal: finalize debit, release lock.
     */
    public function approveWithdrawal(WalletTransaction $txn, int $approvedBy): void
    {
        DB::transaction(function () use ($txn, $approvedBy) {
            $wallet = Wallet::where('id', $txn->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->decrement('balance', $txn->amount);
            $wallet->decrement('locked_balance', $txn->amount);

            $txn->update([
                'status'      => 'completed',
                'type'        => 'debit',
                'metadata'    => array_merge($txn->metadata ?? [], [
                    'approved_by' => $approvedBy,
                    'approved_at' => now()->toISOString(),
                ]),
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }

    /**
     * Admin rejects withdrawal: release the locked funds back to available.
     */
    public function rejectWithdrawal(WalletTransaction $txn, string $reason, int $rejectedBy): void
    {
        DB::transaction(function () use ($txn, $reason, $rejectedBy) {
            $wallet = Wallet::where('id', $txn->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->decrement('locked_balance', $txn->amount);

            $txn->update([
                'status'   => 'cancelled',
                'metadata' => array_merge($txn->metadata ?? [], [
                    'rejected_by'     => $rejectedBy,
                    'rejected_at'     => now()->toISOString(),
                    'rejection_reason'=> $reason,
                ]),
            ]);
        });
    }
}
