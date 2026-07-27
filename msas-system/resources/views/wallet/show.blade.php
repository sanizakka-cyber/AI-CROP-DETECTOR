<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            My Wallet
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Balance Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            {{-- Available Balance --}}
            <div class="col-span-1 sm:col-span-1 bg-gradient-to-br from-[#0F6B3E] to-[#0a5230] rounded-2xl p-6 text-white shadow-lg">
                <p class="text-sm font-medium text-green-200 mb-1">Available Balance</p>
                <p class="text-3xl font-bold tracking-tight">₦{{ number_format($wallet->available_balance, 2) }}</p>
                <p class="text-xs text-green-300 mt-1">{{ $wallet->currency }}</p>
                <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')"
                    class="mt-4 w-full bg-white/20 hover:bg-white/30 text-white text-sm font-medium py-2 rounded-lg transition">
                    Request Withdrawal
                </button>
            </div>

            {{-- Locked Balance --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Withdrawal</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">₦{{ number_format($wallet->locked_balance, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">Awaiting finance approval</p>
            </div>

            {{-- Total Earned --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Earned</p>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">₦{{ number_format($wallet->total_credited, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">Lifetime credits</p>
            </div>
        </div>

        {{-- Transaction History --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Transaction History</h3>
                <span class="text-xs text-gray-400">{{ $transactions->total() }} records</span>
            </div>

            @if($transactions->isEmpty())
                <div class="py-16 text-center">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <p class="text-sm text-gray-400">No transactions yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3 font-medium">Date</th>
                                <th class="px-6 py-3 font-medium">Description</th>
                                <th class="px-6 py-3 font-medium">Reference</th>
                                <th class="px-6 py-3 font-medium text-right">Amount</th>
                                <th class="px-6 py-3 font-medium text-right">Balance</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($transactions as $txn)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $txn->created_at->format('d M Y') }}<br>
                                        <span class="text-xs">{{ $txn->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-800 dark:text-gray-200 max-w-xs">
                                        {{ $txn->description }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ $txn->reference }}</td>
                                    <td class="px-6 py-4 text-right font-semibold font-mono whitespace-nowrap tabular-nums
                                        {{ $txn->isCredit() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $txn->isCredit() ? '+' : '-' }}₦{{ number_format($txn->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap tabular-nums">
                                        ₦{{ number_format($txn->balance_after, 2) }}
                                    </td>
                                    <td class="px-6 py-4">{!! $txn->status_badge !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $transactions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Withdrawal Modal --}}
    <div id="withdrawModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Request Withdrawal</h3>
                <button onclick="document.getElementById('withdrawModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('wallet.withdraw') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Available: <strong class="text-gray-900 dark:text-white">₦{{ number_format($wallet->available_balance, 2) }}</strong>
                        &nbsp;·&nbsp; Minimum: ₦500
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount (₦)</label>
                    <input type="number" name="amount" min="500" max="{{ $wallet->available_balance }}" step="0.01"
                        placeholder="e.g. 5000"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Name</label>
                    <input type="text" name="bank_name" placeholder="e.g. First Bank, GTBank"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number</label>
                    <input type="text" name="account_number" placeholder="10-digit account number"
                        maxlength="10"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Name</label>
                    <input type="text" name="account_name" placeholder="Name on the account"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 text-xs text-yellow-800 dark:text-yellow-200">
                    Withdrawals are reviewed by the finance team and processed within 24–48 hours on business days.
                </div>
                <button type="submit"
                    class="w-full bg-[#0F6B3E] hover:bg-[#0a5230] text-white font-semibold py-3 rounded-xl transition text-sm">
                    Submit Withdrawal Request
                </button>
            </form>
        </div>
    </div>

    @if($errors->any())
        <script>document.getElementById('withdrawModal').classList.remove('hidden');</script>
    @endif
</x-app-layout>
