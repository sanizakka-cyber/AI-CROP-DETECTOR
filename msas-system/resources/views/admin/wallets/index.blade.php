<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Wallet Management</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Total Platform Wallet Balance</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">₦{{ number_format($totalBalance, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Total Locked (Pending)</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">₦{{ number_format($totalLocked, 2) }}</p>
            </div>
            <a href="{{ route('admin.wallets.withdrawals') }}" class="bg-[#0F6B3E] rounded-2xl p-6 shadow text-white hover:bg-[#0a5230] transition flex flex-col justify-between">
                <p class="text-sm font-medium text-green-200">Pending Withdrawals</p>
                <p class="text-3xl font-bold mt-2">→</p>
                <p class="text-xs text-green-300 mt-1">Review &amp; approve requests</p>
            </a>
        </div>

        {{-- Search --}}
        <form method="GET" class="mb-6">
            <div class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email…"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                <button type="submit"
                    class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-medium hover:bg-[#0a5230] transition">
                    Search
                </button>
            </div>
        </form>

        {{-- Wallets Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">All Wallets ({{ $wallets->total() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <th class="px-6 py-3 font-medium">User</th>
                            <th class="px-6 py-3 font-medium">Role</th>
                            <th class="px-6 py-3 font-medium text-right">Balance</th>
                            <th class="px-6 py-3 font-medium text-right">Locked</th>
                            <th class="px-6 py-3 font-medium text-right">Available</th>
                            <th class="px-6 py-3 font-medium">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($wallets as $wallet)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $wallet->user->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $wallet->user->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ $wallet->user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono tabular-nums text-gray-900 dark:text-white">
                                    ₦{{ number_format($wallet->balance, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono tabular-nums text-yellow-600 dark:text-yellow-400">
                                    {{ $wallet->locked_balance > 0 ? '₦' . number_format($wallet->locked_balance, 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono tabular-nums text-green-600 dark:text-green-400 font-semibold">
                                    ₦{{ number_format($wallet->available_balance, 2) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400">
                                    {{ $wallet->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">No wallets found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($wallets->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $wallets->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
