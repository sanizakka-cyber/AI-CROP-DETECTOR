<x-app-layout>
    <x-slot name="header">Income &amp; Expenses</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Finance &amp; Accounts</p>
                <h1 class="text-2xl font-extrabold">Transactions</h1>
                <p class="text-emerald-100 text-sm mt-1">
                    Income: &#8358;{{ number_format($totalIncome) }} &nbsp;|&nbsp; Expenses: &#8358;{{ number_format($totalExpenses) }} &nbsp;|&nbsp;
                    <span class="{{ $netBalance >= 0 ? 'text-emerald-300' : 'text-red-300' }}">Net: &#8358;{{ number_format(abs($netBalance)) }}</span>
                </p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('finance.payroll') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Payroll</a>
                <a href="{{ route('finance.reports') }}" class="px-4 py-2 bg-[#F4A300] hover:bg-[#d4900a] text-white rounded-xl text-sm font-semibold transition">&#9654; Reports</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Add Transaction --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Record Transaction</h3>
                <form method="POST" action="{{ route('finance.transactions.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Type</label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="Income" checked class="accent-[#0F6B3E]">
                                <span class="text-sm font-semibold text-emerald-700">Income</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="Expense" class="accent-red-600">
                                <span class="text-sm font-semibold text-red-600">Expense</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Category</label>
                        <input type="text" name="category" required list="cat-list" placeholder="e.g. Subscription, Salary..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        <datalist id="cat-list">
                            <option value="Subscription Revenue">
                            <option value="Consultation Fee">
                            <option value="Marketplace Commission">
                            <option value="Government Grant">
                            <option value="Salary">
                            <option value="Server Hosting">
                            <option value="Marketing">
                            <option value="Software License">
                            <option value="Office Supplies">
                            <option value="Travel">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Amount (&#8358;)</label>
                        <input type="number" name="amount" required min="0.01" step="0.01"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Date</label>
                        <input type="date" name="transaction_date" required value="{{ date('Y-m-d') }}"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Optional description..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Reference #</label>
                        <input type="text" name="reference" placeholder="Invoice, receipt, etc."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                        Save Transaction
                    </button>
                </form>
            </div>

            {{-- Transactions Table --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <form method="GET" action="{{ route('finance.transactions') }}" class="flex flex-wrap gap-3">
                        <select name="type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">All Types</option>
                            <option value="Income"  {{ request('type') === 'Income'  ? 'selected' : '' }}>Income</option>
                            <option value="Expense" {{ request('type') === 'Expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                        <select name="category" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="from" value="{{ request('from') }}"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        <input type="date" name="to" value="{{ request('to') }}"
                            class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        <button type="submit" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
                        <a href="{{ route('finance.transactions') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Reset</a>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($transactions as $tx)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800 text-xs">
                                    {{ $tx->description ?? 'Transaction' }}
                                    @if($tx->reference)<div class="text-slate-400">#{{ $tx->reference }}</div>@endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $tx->category }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $tx->type === 'Income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                        {{ $tx->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-bold text-xs {{ $tx->type === 'Income' ? 'text-[#1FA84A]' : 'text-red-600' }}">
                                    &#8358;{{ number_format($tx->amount) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ optional($tx->transaction_date)->format('d M Y') ?? $tx->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('finance.transactions.delete', $tx) }}" onsubmit="return confirm('Delete this transaction?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">&#10005;</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">No transactions recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-4 border-t border-slate-100">{{ $transactions->links() }}</div>
            </div>
        </div>

    </div>
</x-app-layout>
