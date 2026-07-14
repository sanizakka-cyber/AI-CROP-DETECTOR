<x-app-layout>
    <x-slot name="header">Payroll Overview</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Finance &amp; Accounts</p>
                <h1 class="text-2xl font-extrabold">Payroll Overview</h1>
                <p class="text-emerald-100 text-sm mt-1">
                    Total Payroll: &#8358;{{ number_format($totalPayroll) }} &nbsp;|&nbsp;
                    Paid: &#8358;{{ number_format($totalPaid) }} &nbsp;|&nbsp;
                    Pending: &#8358;{{ number_format($totalPending) }}
                </p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('finance.transactions') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Transactions</a>
                <a href="{{ route('finance.reports') }}" class="px-4 py-2 bg-[#F4A300] hover:bg-[#d4900a] text-white rounded-xl text-sm font-semibold transition">&#9654; Reports</a>
                <a href="{{ route('hr.payroll') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; HR View</a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('finance.payroll') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Month</label>
                <input type="text" name="month" value="{{ request('month') }}" placeholder="e.g. June 2026"
                    class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Status</label>
                <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid"    {{ request('status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            <a href="{{ route('finance.payroll') }}" class="px-5 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Reset</a>
        </form>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Payroll</p>
                <p class="text-2xl font-extrabold text-slate-800 mt-1">&#8358;{{ number_format($totalPayroll) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Paid</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">&#8358;{{ number_format($totalPaid) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-1">&#8358;{{ number_format($totalPending) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Staff Count</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">{{ $payrolls->total() }}</p>
            </div>
        </div>

        {{-- Payroll Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Payroll Records</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-6 py-3">Staff Member</th>
                            <th class="px-6 py-3">Month</th>
                            <th class="px-6 py-3">Basic</th>
                            <th class="px-6 py-3">Bonus</th>
                            <th class="px-6 py-3">Deductions</th>
                            <th class="px-6 py-3">Net Salary</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Payment Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($payrolls as $pay)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ optional($pay->user)->avatarUrl ?? 'https://ui-avatars.com/api/?name=S&background=64748b&color=fff' }}"
                                        class="w-8 h-8 rounded-full object-cover" alt="">
                                    <div>
                                        <div class="font-semibold text-slate-800 text-xs">{{ optional($pay->user)->name ?? '—' }}</div>
                                        <div class="text-slate-400 text-xs">{{ optional($pay->user)->roleLabel }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-600">{{ $pay->month }}</td>
                            <td class="px-6 py-3 text-xs text-slate-600">&#8358;{{ number_format($pay->basic_salary) }}</td>
                            <td class="px-6 py-3 text-xs text-emerald-600 font-semibold">+&#8358;{{ number_format($pay->bonus ?? 0) }}</td>
                            <td class="px-6 py-3 text-xs text-red-500 font-semibold">-&#8358;{{ number_format($pay->deductions ?? 0) }}</td>
                            <td class="px-6 py-3 text-xs font-bold text-[#0F6B3E]">&#8358;{{ number_format($pay->net_salary) }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $pay->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-700' }}">
                                    {{ ucfirst($pay->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-500">
                                {{ optional($pay->payment_date)->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">No payroll records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">{{ $payrolls->links() }}</div>
        </div>

    </div>
</x-app-layout>
