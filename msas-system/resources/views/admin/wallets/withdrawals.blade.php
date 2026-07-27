<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.wallets.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Withdrawal Requests</h2>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Reject Modal --}}
        <div id="rejectModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Reject Withdrawal</h3>
                    <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="rejectForm" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('POST')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for rejection</label>
                        <textarea name="reason" rows="3" required placeholder="Explain why this withdrawal is being rejected…"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRejectModal()"
                            class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition">
                            Reject & Return Funds
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pending Requests --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700 mb-8">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-bold">
                    {{ $pending->total() }}
                </span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pending Requests</h3>
            </div>

            @if($pending->isEmpty())
                <div class="py-12 text-center text-sm text-gray-400">No pending withdrawal requests.</div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($pending as $txn)
                        @php $meta = $txn->metadata ?? []; @endphp
                        <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $txn->wallet->user->name }}</span>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $txn->wallet->user->role }}</span>
                                </div>
                                <p class="text-xs text-gray-400 mb-2">{{ $txn->wallet->user->email }} &nbsp;·&nbsp; Requested {{ $txn->created_at->diffForHumans() }}</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-1 text-sm">
                                    <div>
                                        <span class="text-xs text-gray-400">Bank</span>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $meta['bank_name'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">Account No.</span>
                                        <p class="font-mono font-medium text-gray-800 dark:text-gray-200">{{ $meta['account_number'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">Account Name</span>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $meta['account_name'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2 font-mono">Ref: {{ $txn->reference }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-3 flex-shrink-0">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">₦{{ number_format($txn->amount, 2) }}</p>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('admin.wallets.withdrawals.reject', $txn) }}">
                                        @csrf
                                        <button type="button"
                                            onclick="openRejectModal('{{ route('admin.wallets.withdrawals.reject', $txn) }}')"
                                            class="px-4 py-2 text-sm rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            Reject
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.wallets.withdrawals.approve', $txn) }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-4 py-2 text-sm rounded-xl bg-[#0F6B3E] hover:bg-[#0a5230] text-white font-medium transition">
                                            Approve
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($pending->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $pending->links() }}</div>
                @endif
            @endif
        </div>

        {{-- History --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Withdrawal History</h3>
            </div>
            @if($history->isEmpty())
                <div class="py-12 text-center text-sm text-gray-400">No processed withdrawals yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-3 font-medium">User</th>
                                <th class="px-6 py-3 font-medium">Bank Details</th>
                                <th class="px-6 py-3 font-medium text-right">Amount</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($history as $txn)
                                @php $meta = $txn->metadata ?? []; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $txn->wallet->user->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $txn->wallet->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $meta['bank_name'] ?? '' }} · {{ $meta['account_number'] ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono tabular-nums font-semibold text-gray-900 dark:text-white">
                                        ₦{{ number_format($txn->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4">{!! $txn->status_badge !!}</td>
                                    <td class="px-6 py-4 text-xs text-gray-400">{{ $txn->updated_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($history->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $history->appends(['page' => $pending->currentPage()])->links('pagination::tailwind', ['pageName' => 'history_page']) }}</div>
                @endif
            @endif
        </div>
    </div>

    <script>
        function openRejectModal(action) {
            document.getElementById('rejectForm').action = action;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
