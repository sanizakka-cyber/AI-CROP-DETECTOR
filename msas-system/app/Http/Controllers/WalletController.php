<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function show()
    {
        $user   = auth()->user();
        $wallet = $this->walletService->getOrCreate($user);

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return view('wallet.show', compact('wallet', 'transactions'));
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:500|max:5000000',
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name'   => 'required|string|max:100',
        ]);

        $user   = auth()->user();
        $wallet = $this->walletService->getOrCreate($user);

        if ($wallet->available_balance < $request->amount) {
            return back()->withErrors(['amount' => 'Amount exceeds your available balance of ₦' . number_format($wallet->available_balance, 2)]);
        }

        try {
            $this->walletService->requestWithdrawal($user, (float) $request->amount, [
                'bank_name'      => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name'   => $request->account_name,
            ]);

            return back()->with('success', 'Withdrawal request submitted. Finance team will process it within 24–48 hours.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
