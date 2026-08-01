<x-app-layout>
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Input Supplier Dashboard</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome, {{ auth()->user()->first_name }} — manage your agricultural inputs and supply orders</p>
        </div>
        <a href="{{ route('marketplace.sell') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            List Input
        </a>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'My Listings',   'value'=>$myListings,                        'sub'=>"{$activeListings} active",   'color'=>'#0288D1', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>'],
            ['label'=>'Total Orders',  'value'=>$totalOrders,                       'sub'=>"{$pendingOrders} pending",   'color'=>'#d97706', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>'],
            ['label'=>'Revenue',       'value'=>'₦'.number_format($totalRevenue),   'sub'=>'from paid orders',          'color'=>'#0F6B3E', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['label'=>'Top Categories','value'=>$topCategories->count(),             'sub'=>'product categories',        'color'=>'#7C3AED', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
        ] as $c)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:{{ $c['color'] }}18">
                    <svg width="18" height="18" fill="none" stroke="{{ $c['color'] }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $c['path'] !!}</svg>
                </div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $c['label'] }}</span>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $c['value'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $c['sub'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Categories --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-extrabold text-slate-700 mb-4">Top Supply Categories</h2>
            @if($topCategories->isEmpty())
            <p class="text-sm text-slate-400 text-center py-8">No products listed yet.</p>
            @else
            <div class="space-y-3">
                @php $maxCount = $topCategories->max('count') ?: 1; @endphp
                @foreach($topCategories as $cat)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700">{{ $cat->category }}</span>
                        <span class="text-slate-400 font-semibold">{{ $cat->count }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width:{{ ($cat->count / $maxCount) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Recent Orders --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-extrabold text-slate-700">Recent Orders</h2>
            </div>
            @if(collect($recentOrders)->isEmpty())
            <div class="text-center py-12 text-slate-400">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:#d9770618">
                    <svg width="28" height="28" fill="none" stroke="#d97706" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-sm font-semibold">No orders yet</p>
            </div>
            @else
            <div class="divide-y divide-slate-100">
                @foreach($recentOrders as $o)
                <div class="flex items-center justify-between px-6 py-3">
                    <div>
                        <p class="text-xs font-mono text-slate-500">{{ $o->order_number }}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold mt-0.5
                            {{ $o->status === 'delivered' ? 'bg-green-100 text-green-700' : ($o->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                            {{ ucfirst($o->status) }}
                        </span>
                    </div>
                    <p class="font-semibold text-slate-700">₦{{ number_format($o->total) }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach([
            ['label'=>'List Input',   'color'=>'#0288D1', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',                                                                                                                                                                   'route'=>'marketplace.sell', 'desc'=>'Add fertilizer, seed, chemical…'],
            ['label'=>'Marketplace',  'color'=>'#d97706', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',                                                                           'route'=>'marketplace',      'desc'=>'Browse all products'],
            ['label'=>'My Profile',   'color'=>'#7C3AED', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',                                                                                                                                                              'route'=>'profile.edit',     'desc'=>'Edit account details'],
        ] as $a)
        <a href="{{ route($a['route']) }}"
           class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:{{ $a['color'] }}1a">
                <svg width="22" height="22" fill="none" stroke="{{ $a['color'] }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $a['path'] !!}</svg>
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ $a['label'] }}</p>
                <p class="text-xs text-slate-400">{{ $a['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
</x-app-layout>
