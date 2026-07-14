<x-app-layout>
    <x-slot name="header">Financial Reports</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Finance &amp; Accounts</p>
                <h1 class="text-2xl font-extrabold">Financial Reports — {{ date('Y') }}</h1>
                <p class="text-emerald-100 text-sm mt-1">
                    Annual Income: &#8358;{{ number_format($annualIncome) }} &nbsp;|&nbsp;
                    Annual Expenses: &#8358;{{ number_format($annualExpenses) }} &nbsp;|&nbsp;
                    <span class="{{ ($annualIncome - $annualExpenses) >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                        Net: &#8358;{{ number_format(abs($annualIncome - $annualExpenses)) }}
                    </span>
                </p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('finance.transactions') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Transactions</a>
                <a href="{{ route('finance.payroll') }}"      class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Payroll</a>
            </div>
        </div>

        {{-- Annual KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Income</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">&#8358;{{ number_format($annualIncome) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-red-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Expenses</p>
                <p class="text-2xl font-extrabold text-red-600 mt-1">&#8358;{{ number_format($annualExpenses) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Payroll Cost</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">&#8358;{{ number_format($annualPayroll) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-{{ ($annualIncome - $annualExpenses) >= 0 ? '[#0F6B3E]' : 'red-500' }}">
                <p class="text-xs font-bold text-slate-500 uppercase">Net Profit/Loss</p>
                <p class="text-2xl font-extrabold {{ ($annualIncome - $annualExpenses) >= 0 ? 'text-[#0F6B3E]' : 'text-red-600' }} mt-1">
                    {{ ($annualIncome - $annualExpenses) >= 0 ? '' : '-' }}&#8358;{{ number_format(abs($annualIncome - $annualExpenses)) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Monthly Breakdown --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">Monthly Breakdown — {{ date('Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                                <th class="px-5 py-3">Month</th>
                                <th class="px-5 py-3 text-emerald-600">Income</th>
                                <th class="px-5 py-3 text-red-500">Expenses</th>
                                <th class="px-5 py-3">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($monthlyData as $row)
                            @php $net = $row['income'] - $row['expenses']; @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-2.5 font-semibold text-slate-700 text-xs">{{ $row['month'] }}</td>
                                <td class="px-5 py-2.5 text-xs text-emerald-600 font-semibold">
                                    {{ $row['income'] > 0 ? '&#8358;'.number_format($row['income']) : '—' }}
                                </td>
                                <td class="px-5 py-2.5 text-xs text-red-500 font-semibold">
                                    {{ $row['expenses'] > 0 ? '&#8358;'.number_format($row['expenses']) : '—' }}
                                </td>
                                <td class="px-5 py-2.5 text-xs font-bold {{ $net >= 0 ? 'text-[#0F6B3E]' : 'text-red-600' }}">
                                    {{ $net != 0 ? ($net >= 0 ? '' : '-').'&#8358;'.number_format(abs($net)) : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                            <tr class="text-xs font-bold">
                                <td class="px-5 py-3 text-slate-600">TOTAL</td>
                                <td class="px-5 py-3 text-emerald-700">&#8358;{{ number_format($annualIncome) }}</td>
                                <td class="px-5 py-3 text-red-600">&#8358;{{ number_format($annualExpenses) }}</td>
                                <td class="px-5 py-3 {{ ($annualIncome - $annualExpenses) >= 0 ? 'text-[#0F6B3E]' : 'text-red-600' }}">
                                    &#8358;{{ number_format(abs($annualIncome - $annualExpenses)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Category Breakdown --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">By Category</h3>
                </div>
                @if($categoryData->isEmpty())
                <p class="text-center text-slate-400 text-sm py-12">No transactions recorded.</p>
                @else
                <div class="divide-y divide-slate-50">
                    @foreach($categoryData as $cat)
                    @php
                        $maxAmount = $categoryData->max('total');
                        $pct = $maxAmount > 0 ? round(($cat->total / $maxAmount) * 100) : 0;
                    @endphp
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $cat->type === 'Income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                    {{ $cat->type }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $cat->category }}</span>
                            </div>
                            <span class="text-xs font-bold {{ $cat->type === 'Income' ? 'text-emerald-700' : 'text-red-600' }}">
                                &#8358;{{ number_format($cat->total) }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $cat->type === 'Income' ? 'bg-emerald-500' : 'bg-red-400' }}"
                                style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        {{-- Payroll Summary --}}
        @if($payrollByMonth->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Monthly Payroll Cost</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-6 py-3">Month</th>
                            <th class="px-6 py-3">Staff Count</th>
                            <th class="px-6 py-3">Total Net Salary</th>
                            <th class="px-6 py-3">Paid</th>
                            <th class="px-6 py-3">Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($payrollByMonth as $pm)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 text-xs font-semibold text-slate-700">{{ $pm->month }}</td>
                            <td class="px-6 py-3 text-xs text-slate-600">{{ $pm->count }}</td>
                            <td class="px-6 py-3 text-xs font-bold text-[#0F6B3E]">&#8358;{{ number_format($pm->total) }}</td>
                            <td class="px-6 py-3 text-xs text-emerald-600 font-semibold">&#8358;{{ number_format($pm->paid) }}</td>
                            <td class="px-6 py-3 text-xs text-amber-600 font-semibold">&#8358;{{ number_format($pm->pending) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
