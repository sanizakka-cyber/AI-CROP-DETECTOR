<x-app-layout>
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Agribusiness Dashboard</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome, {{ auth()->user()->first_name }} — manage your enterprise products and orders</p>
        </div>
        <a href="{{ route('marketplace.sell') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            List Product
        </a>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'My Listings',     'value'=>$myListings,                        'sub'=>"{$activeListings} active",       'icon'=>'📦', 'color'=>'blue'],
            ['label'=>'Total Orders',     'value'=>$totalOrders,                       'sub'=>"{$pendingOrders} pending",        'icon'=>'🛒', 'color'=>'amber'],
            ['label'=>'Revenue',          'value'=>'₦'.number_format($totalRevenue),   'sub'=>'from paid orders',               'icon'=>'💰', 'color'=>'green'],
            ['label'=>'Farmers Nearby',   'value'=>number_format($farmersInState),     'sub'=>auth()->user()->state ?? 'your state', 'icon'=>'🌾', 'color'=>'emerald'],
        ] as $c)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">{{ $c['icon'] }}</span>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $c['label'] }}</span>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $c['value'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $c['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach([
            ['label'=>'List Product', 'icon'=>'📦', 'route'=>'marketplace.sell', 'desc'=>'Add to marketplace'],
            ['label'=>'View Orders',  'icon'=>'🛒', 'route'=>'marketplace',       'desc'=>'All incoming orders'],
            ['label'=>'Marketplace',  'icon'=>'🏪', 'route'=>'marketplace',       'desc'=>'Browse all products'],
        ] as $a)
        <a href="{{ route($a['route']) }}"
           class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-4">
            <div class="text-3xl">{{ $a['icon'] }}</div>
            <div>
                <p class="font-bold text-slate-700">{{ $a['label'] }}</p>
                <p class="text-xs text-slate-400">{{ $a['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-extrabold text-slate-700">Recent Orders</h2>
        </div>
        @if(collect($recentOrders)->isEmpty())
        <div class="text-center py-12 text-slate-400">
            <p class="text-4xl mb-2">🛒</p>
            <p class="font-semibold">No orders yet</p>
            <p class="text-sm mt-1">List products on the marketplace to start receiving orders.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Order #</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($recentOrders as $o)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $o->order_number }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $o->status === 'delivered' ? 'bg-green-100 text-green-700' : ($o->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                            {{ ucfirst($o->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right font-semibold">₦{{ number_format($o->total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
</x-app-layout>
