<x-app-layout>
    <x-slot name="header">Investor Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-yellow-700 to-amber-500 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/10 rounded-full blur-3xl"></div>
            <p class="text-yellow-100 text-sm mb-1">Investor Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-yellow-100 text-sm mt-2">Monitor platform growth, transaction volume, and agricultural market metrics.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-600 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Farmers</p>
                <p class="text-4xl font-extrabold text-emerald-700 mt-2">{{ $totalFarmers }}</p>
                <p class="text-xs text-slate-400 mt-1">Registered on platform</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-amber-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Platform Revenue</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-2">₦{{ number_format($totalRevenue) }}</p>
                <p class="text-xs text-slate-400 mt-1">Successful payments</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Transactions</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $totalTransacts }}</p>
                <p class="text-xs text-slate-400 mt-1">All time</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-purple-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Market Products</p>
                <p class="text-4xl font-extrabold text-purple-600 mt-2">{{ $marketProducts }}</p>
                <p class="text-xs text-slate-400 mt-1">Active listings</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition shadow-sm">Browse Marketplace</a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">My Profile</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Monthly Revenue Trend</h3>
            <div class="flex items-end gap-3 h-32">
                @php $maxAmt = $monthlyRevenue->max('amount') ?: 1; @endphp
                @foreach($monthlyRevenue as $m)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full bg-amber-400 rounded-t-lg transition-all" style="height: {{ max(8, ($m['amount'] / $maxAmt) * 100) }}px"></div>
                    <p class="text-xs text-slate-500">{{ $m['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
