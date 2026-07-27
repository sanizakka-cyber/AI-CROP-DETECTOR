<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletManagementController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request)
    {
        $wallets = Wallet::with('user')
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('first_name', 'ilike', "%{$request->search}%")
                  ->orWhere('last_name', 'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%")
            ))
            ->orderByDesc('balance')
            ->paginate(25)
            ->withQueryString();

        $totalBalance = Wallet::sum('balance');
        $totalLocked  = Wallet::sum('locked_balance');

        return view('admin.wallets.index', compact('wallets', 'totalBalance', 'totalLocked'));
    }

    public function withdrawals(Request $request)
    {
        $pending = WalletTransaction::with('wallet.user')
            ->where('type', 'hold')
            ->where('status', 'pending')
            ->latest()
            ->paginate(25);

        $history = WalletTransaction::with('wallet.user')
            ->where('type', 'debit')
            ->whereIn('status', ['completed', 'cancelled'])
            ->whereNotNull('metadata->bank_name')
            ->latest()
            ->paginate(25, ['*'], 'history_page');

        return view('admin.wallets.withdrawals', compact('pending', 'history'));
    }

    public function approveWithdrawal(WalletTransaction $transaction)
    {
        abort_if($transaction->status !== 'pending', 422, 'This request is not pending.');

        try {
            $this->walletService->approveWithdrawal($transaction, auth()->id());

            // Notify user
            \App\Models\Notification::create([
                'user_id' => $transaction->wallet->user_id,
                'title'   => 'Withdrawal Approved',
                'message' => '₦' . number_format($transaction->amount, 2) . ' withdrawal approved. Funds will be sent to your bank account shortly.',
                'type'    => 'success',
                'link'    => '/wallet',
            ]);

            return back()->with('success', '₦' . number_format($transaction->amount, 2) . ' withdrawal approved.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function rejectWithdrawal(Request $request, WalletTransaction $transaction)
    {
        abort_if($transaction->status !== 'pending', 422, 'This request is not pending.');

        $request->validate(['reason' => 'required|string|max:500']);

        $this->walletService->rejectWithdrawal($transaction, $request->reason, auth()->id());

        // Notify user
        \App\Models\Notification::create([
            'user_id' => $transaction->wallet->user_id,
            'title'   => 'Withdrawal Rejected',
            'message' => 'Your ₦' . number_format($transaction->amount, 2) . ' withdrawal was rejected. Funds returned to wallet. Reason: ' . $request->reason,
            'type'    => 'warning',
            'link'    => '/wallet',
        ]);

        return back()->with('success', 'Withdrawal request rejected. Funds returned to user wallet.');
    }
}
