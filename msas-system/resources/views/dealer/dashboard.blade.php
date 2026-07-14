<x-app-layout>
    <x-slot name="header">Agro-Dealer Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-[#b45309] to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-amber-400/10 rounded-full blur-3xl"></div>
            <p class="text-amber-200 text-sm mb-1">Agro-Dealer Portal</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-amber-100 text-sm mt-2">Manage your marketplace listings, orders, and inventory.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">My Products</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ $myListings }}</p>
                <p class="text-xs text-slate-400 mt-1">Total in catalog</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A] border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Active</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ $activeListings }}</p>
                <p class="text-xs text-slate-400 mt-1">Currently visible</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-amber-500 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending Orders</p>
                <p class="text-4xl font-extrabold text-amber-600 mt-2">{{ $pendingOrders }}</p>
                <p class="text-xs text-slate-400 mt-1">Awaiting confirmation</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-emerald-400 border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase">Revenue (Paid)</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-2">₦{{ number_format($revenue ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Lifetime earnings</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('dealer.products.index') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    📦 Manage Products
                </a>
                <a href="{{ route('dealer.products.create') }}" class="px-5 py-2.5 bg-amber-600 text-white rounded-xl text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    + Add Product
                </a>
                <a href="{{ route('dealer.orders') }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    🛍 View Orders
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    ✏ Edit Profile
                </a>
            </div>
        </div>

        {{-- Recent Products Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-slate-800 text-lg">Recent Products</h3>
                <a href="{{ route('dealer.products.index') }}" class="text-[#1FA84A] text-sm font-semibold hover:underline">View all &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Product</th>
                            <th class="pb-3 pr-4">Price</th>
                            <th class="pb-3 pr-4">Stock</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3 pr-4">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentItems as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $item->name }}</td>
                            <td class="py-3 pr-4 text-slate-700 font-semibold">₦{{ number_format($item->selling_price) }}</td>
                            <td class="py-3 pr-4">
                                @php $qty = $item->quantity_in_stock; $low = $item->low_stock_threshold ?? 10; @endphp
                                <span class="text-xs font-bold {{ $qty === 0 ? 'text-red-600' : ($qty <= $low ? 'text-amber-600' : 'text-emerald-600') }}">
                                    {{ $qty }} units
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $item->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-slate-500 text-xs">{{ $item->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center">
                                <p class="text-slate-500 text-sm">No products yet.</p>
                                <a href="{{ route('dealer.products.create') }}" class="text-[#1FA84A] text-sm font-semibold hover:underline mt-1 inline-block">Add your first product &rarr;</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
