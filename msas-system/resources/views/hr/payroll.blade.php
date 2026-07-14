<x-app-layout>
    <x-slot name="header">Payroll Management</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-blue-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Human Resources</p>
                <h1 class="text-2xl font-extrabold">Payroll Management</h1>
                <p class="text-blue-100 text-sm mt-1">Total Paid: &#8358;{{ number_format($totalPaid) }} &mdash; Pending: &#8358;{{ number_format($pendingPay) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('hr.staff') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Staff</a>
                <a href="{{ route('finance.payroll') }}" class="px-4 py-2 bg-[#F4A300] hover:bg-[#d4900a] text-white rounded-xl text-sm font-semibold transition">&#9654; Finance View</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Add Payroll --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Add / Update Payroll</h3>
                <form method="POST" action="{{ route('hr.payroll.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Staff Member</label>
                        <select name="user_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">Select staff...</option>
                            @foreach($staffList as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->roleLabel }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Month (e.g. June 2026)</label>
                        <input type="text" name="month" required placeholder="June 2026"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Basic Salary (&#8358;)</label>
                        <input type="number" name="basic_salary" required min="0" step="0.01"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Bonus (&#8358;)</label>
                            <input type="number" name="bonus" min="0" step="0.01" value="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Deductions (&#8358;)</label>
                            <input type="number" name="deductions" min="0" step="0.01" value="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Payment Date (if paid)</label>
                        <input type="date" name="payment_date"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                        Save Payroll Record
                    </button>
                </form>
            </div>

            {{-- Payroll Table --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <form method="GET" action="{{ route('hr.payroll') }}" class="flex gap-3">
                        <input type="text" name="month" value="{{ request('month') }}" placeholder="Filter by month..."
                            class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid"    {{ request('status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                                <th class="px-4 py-3">Staff</th>
                                <th class="px-4 py-3">Month</th>
                                <th class="px-4 py-3">Basic</th>
                                <th class="px-4 py-3">Net</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($payrolls as $pay)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800 text-xs">{{ optional($pay->user)->name ?? '—' }}</div>
                                    <div class="text-slate-400 text-xs">{{ optional($pay->user)->roleLabel }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $pay->month }}</td>
                                <td class="px-4 py-3 text-xs font-semibold text-slate-700">&#8358;{{ number_format($pay->basic_salary) }}</td>
                                <td class="px-4 py-3 text-xs font-bold text-[#0F6B3E]">&#8358;{{ number_format($pay->net_salary) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $pay->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($pay->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($pay->status === 'pending')
                                    <form method="POST" action="{{ route('hr.payroll.paid', $pay) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">
                                            Mark Paid
                                        </button>
                                    </form>
                                    @else
                                        <span class="text-xs text-slate-400">{{ optional($pay->payment_date)->format('d M Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">No payroll records yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-4 border-t border-slate-100">{{ $payrolls->links() }}</div>
            </div>
        </div>

    </div>
</x-app-layout>
