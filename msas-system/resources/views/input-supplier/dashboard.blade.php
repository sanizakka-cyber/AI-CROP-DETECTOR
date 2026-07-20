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
            ['label'=>'My Listings',   'value'=>$myListings,                        'sub'=>"{$activeListings} active",   'icon'=>'📦', 'color'=>'blue'],
            ['label'=>'Total Orders',  'value'=>$totalOrders,                       'sub'=>"{$pendingOrders} pending",    'icon'=>'🛒', 'color'=>'amber'],
            ['label'=>'Revenue',       'value'=>'₦'.number_format($totalRevenue),   'sub'=>'from paid orders',           'icon'=>'💰', 'color'=>'green'],
            ['label'=>'Top Categories','value'=>$topCategories->count(),             'sub'=>'product categories',         'icon'=>'🗂', 'color'=>'purple'],
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
                <p class="text-4xl mb-2">🛒</p>
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
            ['label'=>'List Input',   'icon'=>'📦', 'route'=>'marketplace.sell', 'desc'=>'Add fertilizer, seed, chemical…'],
            ['label'=>'Marketplace',  'icon'=>'🏪', 'route'=>'marketplace',      'desc'=>'Browse all products'],
            ['label'=>'My Profile',   'icon'=>'👤', 'route'=>'profile.edit',     'desc'=>'Edit account details'],
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
</div>
</x-app-layout>
