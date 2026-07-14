<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Financial Ledger') }}
            </h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-blue-700">
                + Record Transaction
            </button>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-emerald-500">
                    <div class="text-sm font-bold text-slate-500 uppercase">Total Income</div>
                    <div class="text-3xl font-extrabold text-emerald-600 mt-1">₦{{ number_format($totalIncome) }}</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <div class="text-sm font-bold text-slate-500 uppercase">Total Expenses</div>
                    <div class="text-3xl font-extrabold text-red-600 mt-1">₦{{ number_format($totalExpense) }}</div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <div class="text-sm font-bold text-slate-500 uppercase">Net Balance</div>
                    <div class="text-3xl font-extrabold text-blue-600 mt-1">₦{{ number_format($totalIncome - $totalExpense) }}</div>
                </div>
            </div>
            
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-4 rounded-xl font-bold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 overflow-hidden">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg mb-4 text-slate-800 border-b pb-2">Recent Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Date</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">Amount (₦)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($finances as $finance)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($finance->transaction_date)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">
                                            @if($finance->type === 'Income')
                                                <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 font-bold text-[10px] uppercase">Income</span>
                                            @else
                                                <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-bold text-[10px] uppercase">Expense</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold">{{ $finance->category }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($finance->description, 30) }}</td>
                                        <td class="px-4 py-3 text-right font-extrabold {{ $finance->type === 'Income' ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $finance->type === 'Income' ? '+' : '-' }}{{ number_format($finance->amount) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                            <div class="text-4xl mb-2 opacity-50">🧾</div>
                                            No financial records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                <h3 class="font-bold">Record Transaction</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-white hover:text-blue-200 font-bold">&times;</button>
            </div>
            <form action="{{ route('farmer.finance.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type *</label>
                            <select name="type" required class="w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="Income">Income (+)</option>
                                <option value="Expense">Expense (-)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Date *</label>
                            <input type="date" name="transaction_date" required value="{{ date('Y-m-d') }}" class="w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Category *</label>
                        <select name="category" required class="w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="Animal Sale">Animal Sale</option>
                            <option value="Egg Sale">Egg Sale</option>
                            <option value="Feed Purchase">Feed Purchase</option>
                            <option value="Medication">Medication/Vet</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Amount (₦) *</label>
                        <input type="number" name="amount" min="0" required class="w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold shadow hover:bg-blue-700 transition">
                        Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
