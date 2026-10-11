<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    public function __construct(private WalletService $wallet) {}

    public function balance(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (! $wallet) {
            return response()->json(['balance' => 0, 'available_balance' => 0, 'locked_balance' => 0, 'transactions' => []]);
        }
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->take(20)
            ->get(['id','type','amount','description','reference','balance_after','created_at']);
        return response()->json([
            'balance'           => $wallet->balance ?? 0,
            'available_balance' => $wallet->available_balance ?? 0,
            'locked_balance'    => $wallet->locked_balance ?? 0,
            'transactions'      => $transactions,
        ]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'amount'         => 'required|numeric|min:100',
            'account_number' => 'required|string|max:20',
            'bank_name'      => 'required|string|max:100',
            'account_name'   => 'required|string|max:150',
        ]);
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (! $wallet || $wallet->available_balance < $data['amount']) {
            return response()->json(['error' => 'Insufficient balance.'], 422);
        }
        try {
            // Previously called debit() directly -- an immediate, completed
            // debit with no admin review and no linkage to an actual bank
            // payout. The web WalletController correctly uses
            // requestWithdrawal() instead, which moves the funds into
            // locked_balance pending Admin\WalletManagementController::
            // approveWithdrawal()/rejectWithdrawal() -- mobile users were
            // getting their balance debited instantly with zero
            // reconciliation trail, a real risk for "my balance dropped
            // but I never got paid" disputes.
            $this->wallet->requestWithdrawal($user, $data['amount'], [
                'account_name'   => $data['account_name'],
                'account_number' => $data['account_number'],
                'bank_name'      => $data['bank_name'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        return response()->json([
            'message'           => 'Withdrawal request submitted. Processing within 24 hours.',
            'amount'            => $data['amount'],
            'available_balance' => $wallet->fresh()->available_balance ?? 0,
        ]);
    }
}