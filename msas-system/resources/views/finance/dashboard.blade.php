<x-app-layout>
    <x-slot name="header">Finance Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Finance &amp; Accounts</p>
            <h1 class="text-3xl font-extrabold">Finance Dashboard</h1>
            <p class="text-emerald-100 text-sm mt-2">Platform financial overview &mdash; income, expenses, and net performance.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Income</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">&#8358;{{ number_format($totalIncome) }}</p>
                <p class="text-xs text-slate-400 mt-1">This month: &#8358;{{ number_format($thisMonthIncome) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-red-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Expenses</p>
                <p class="text-4xl font-extrabold text-red-600 mt-2">&#8358;{{ number_format($totalExpenses) }}</p>
                <p class="text-xs text-slate-400 mt-1">This month: &#8358;{{ number_format($thisMonthExpenses) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 {{ $netProfit >= 0 ? 'border-l-[#0F6B3E]' : 'border-l-red-700' }}">
                <p class="text-xs font-bold text-slate-500 uppercase">Net Profit</p>
                <p class="text-4xl font-extrabold {{ $netProfit >= 0 ? 'text-[#0F6B3E]' : 'text-red-700' }} mt-2">
                    &#8358;{{ number_format(abs($netProfit)) }}
                    @if($netProfit < 0) <span class="text-base text-red-500">(Loss)</span> @endif
                </p>
                <p class="text-xs text-slate-400 mt-1">Cumulative net position</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('finance.transactions') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Transactions
                </a>
                <a href="{{ route('finance.payroll') }}" class="px-5 py-2.5 bg-slate-700 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-sm">
                    &#9654; Payroll Overview
                </a>
                <a href="{{ route('finance.reports') }}" class="px-5 py-2.5 bg-[#F4A300] text-white rounded-xl text-sm font-semibold hover:bg-[#d4900a] transition shadow-sm">
                    &#9654; Financial Reports
                </a>
            </div>
        </div>

        {{-- Monthly Chart (visual bar chart) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-6 border-b pb-3">Monthly Overview (Last 6 Months)</h3>
            @php
                $maxVal = $monthlyChart->flatMap(fn($m) => [$m['income'], $m['expense']])->max() ?: 1;
            @endphp
            <div class="flex items-end gap-3 h-40 mt-2">
                @foreach($monthlyChart as $month)
                @php
                    $incH = max(4, round(($month['income'] / $maxVal) * 120));
                    $expH = max(4, round(($month['expense'] / $maxVal) * 120));
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex gap-1 items-end justify-center" style="height: 120px">
                        <div class="w-5 rounded-t bg-[#1FA84A]" style="height: {{ $incH }}px" title="Income: {{ number_format($month['income']) }}"></div>
                        <div class="w-5 rounded-t bg-red-400" style="height: {{ $expH }}px" title="Expense: {{ number_format($month['expense']) }}"></div>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">{{ $month['month'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="flex gap-4 mt-4 justify-center text-xs">
                <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded bg-[#1FA84A]"></div> Income</div>
                <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded bg-red-400"></div> Expenses</div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Description</th>
                            <th class="pb-3 pr-4">Type</th>
                            <th class="pb-3 pr-4">Amount</th>
                            <th class="pb-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $tx->description ?? 'Transaction' }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ strtolower($tx->type ?? '') === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 font-semibold {{ strtolower($tx->type ?? '') === 'income' ? 'text-[#1FA84A]' : 'text-red-600' }}">
                                &#8358;{{ number_format($tx->amount) }}
                            </td>
                            <td class="py-3 text-slate-500 text-xs">{{ optional($tx->transaction_date)->format('M d, Y') ?? $tx->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-sm">No transactions recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
