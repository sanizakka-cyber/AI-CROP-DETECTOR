<x-app-layout>
    <x-slot name="header">Equipment Dealer Dashboard</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/5 rounded-full blur-3xl"></div>
            <p class="text-slate-300 text-sm mb-1">Equipment Dealer Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-slate-300 text-sm mt-2">Manage your equipment listings, orders, and customer enquiries.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-slate-700 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">My Listings</p>
                <p class="text-4xl font-extrabold text-slate-700 mt-2">{{ $totalProducts }}</p>
                <p class="text-xs text-slate-400 mt-1">Products listed</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-amber-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending Orders</p>
                <p class="text-4xl font-extrabold text-amber-500 mt-2">{{ $pendingOrders }}</p>
                <p class="text-xs text-slate-400 mt-1">Awaiting action</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Orders</p>
                <p class="text-4xl font-extrabold text-emerald-600 mt-2">{{ $totalOrders }}</p>
                <p class="text-xs text-slate-400 mt-1">All time</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Revenue</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-2">₦{{ number_format($totalRevenue) }}</p>
                <p class="text-xs text-slate-400 mt-1">Confirmed sales</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('equipment-dealer.products.index') }}" class="px-5 py-2.5 bg-slate-700 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-sm">My Listings</a>
                <a href="{{ route('equipment-dealer.products.create') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">+ Add Equipment</a>
                <a href="{{ route('equipment-dealer.orders') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Orders</a>
                <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Marketplace</a>
            </div>
        </div>

        @if($recentOrders->count())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Orders</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs font-bold text-slate-500 uppercase text-left">
                        <th class="pb-3 pr-4">Reference</th><th class="pb-3 pr-4">Amount</th><th class="pb-3">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($recentOrders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-mono text-xs text-slate-600">{{ $order->reference ?? '#'.$order->id }}</td>
                            <td class="py-3 pr-4 font-bold text-emerald-700">₦{{ number_format($order->total ?? 0) }}</td>
                            <td class="py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($order->status ?? 'pending') }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
