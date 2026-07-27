<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-gray-800">Admin Operational Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">MSAS FarmAI — Operational Control Centre</p>
            </div>
            <span class="text-sm font-semibold text-slate-500">{{ now()->format('D, d M Y · H:i') }}</span>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Flash --}}
        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        {{-- ── Welcome ── --}}
        <div class="relative overflow-hidden rounded-2xl p-7 text-white shadow-lg" style="background:linear-gradient(135deg,#0B2447,#0F6B3E);">
            <div class="absolute -right-8 -top-8 w-48 h-48 rounded-full bg-white/5"></div>
            <p class="text-emerald-200 text-sm font-medium mb-1">System Administrator</p>
            <h1 class="text-2xl font-extrabold">Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->displayFirstName ?? 'Admin' }}</h1>
            <p class="text-emerald-100 text-sm mt-1">Here is your operational overview for today.</p>
        </div>

        {{-- ── Attention Required Banner ── --}}
        @php
            $urgent = ($ordersUnassigned ?? 0) + ($consultsUnassigned ?? 0) + ($pendingVerifications ?? 0);
        @endphp
        @if($urgent > 0)
        <div class="rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="font-extrabold text-red-800 text-sm">{{ $urgent }} item{{ $urgent !== 1 ? 's' : '' }} need your attention</span>
            </div>
            <div class="grid sm:grid-cols-3 gap-3">
                @if($ordersUnassigned ?? 0)
                <a href="{{ route('admin.orders.index', ['rider_status'=>'unassigned']) }}"
                   class="flex items-center justify-between bg-white border border-red-200 rounded-xl px-4 py-3 hover:border-red-400 hover:shadow-sm transition group">
                    <div>
                        <div class="text-2xl font-extrabold text-red-600">{{ $ordersUnassigned }}</div>
                        <div class="text-xs font-bold text-slate-600 mt-0.5">Unassigned Orders</div>
                        <div class="text-[10px] text-slate-400">Click to assign riders →</div>
                    </div>
                    <svg class="w-5 h-5 text-red-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
                @if($consultsUnassigned ?? 0)
                <a href="{{ route('admin.consultations.index', ['assigned'=>'unassigned']) }}"
                   class="flex items-center justify-between bg-white border border-orange-200 rounded-xl px-4 py-3 hover:border-orange-400 hover:shadow-sm transition group">
                    <div>
                        <div class="text-2xl font-extrabold text-orange-600">{{ $consultsUnassigned }}</div>
                        <div class="text-xs font-bold text-slate-600 mt-0.5">Unassigned Consultations</div>
                        <div class="text-[10px] text-slate-400">Click to assign experts →</div>
                    </div>
                    <svg class="w-5 h-5 text-orange-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
                @if($pendingVerifications ?? 0)
                <a href="{{ route('admin.applications.index') }}"
                   class="flex items-center justify-between bg-white border border-amber-200 rounded-xl px-4 py-3 hover:border-amber-400 hover:shadow-sm transition group">
                    <div>
                        <div class="text-2xl font-extrabold text-amber-600">{{ $pendingVerifications }}</div>
                        <div class="text-xs font-bold text-slate-600 mt-0.5">Pending Verifications</div>
                        <div class="text-[10px] text-slate-400">Click to review applications →</div>
                    </div>
                    <svg class="w-5 h-5 text-amber-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Platform KPIs ── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
            $kpis = [
                ['label'=>'Total Users',   'val'=>number_format($totalUsers),    'sub'=>number_format($activeUsers).' active',  'color'=>'#0F6B3E', 'border'=>'border-l-[#0F6B3E]', 'href'=>route('admin.users')],
                ['label'=>'New This Month','val'=>$newThisMonth,                'sub'=>'Registrations',                       'color'=>'#2563eb', 'border'=>'border-l-blue-500',    'href'=>route('admin.users')],
                ['label'=>'Pending Verify','val'=>$pendingApprovals,            'sub'=>'Expert verifications',                'color'=>'#f59e0b', 'border'=>'border-l-amber-500',   'href'=>route('admin.applications.index')],
                ['label'=>'Consultations', 'val'=>number_format($totalConsults),'sub'=>($consultsOpen??0).' open',            'color'=>'#8b5cf6', 'border'=>'border-l-purple-500',  'href'=>route('admin.consultations.index')],
            ];
            @endphp
            @foreach($kpis as $k)
            <a href="{{ $k['href'] }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 border-l-4 {{ $k['border'] }} p-5 hover:shadow-md transition group">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $k['label'] }}</p>
                <p class="text-3xl font-black mt-1 group-hover:opacity-80" style="color:{{ $k['color'] }}">{{ $k['val'] }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $k['sub'] }}</p>
            </a>
            @endforeach
        </div>

        {{-- ── Module Quick-Access Grid ── --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @php
            $modules = [
                ['label'=>'Orders',        'sub'=>($ordersPending??0).' pending',     'href'=>route('admin.orders.index'),        'color'=>'#f59e0b', 'bg'=>'bg-amber-50',  'icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['label'=>'Consultations', 'sub'=>($consultsOpen??0).' open',         'href'=>route('admin.consultations.index'), 'color'=>'#8b5cf6', 'bg'=>'bg-purple-50', 'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ['label'=>'Riders',        'sub'=>($ridersAvailable??0).' available', 'href'=>route('admin.riders.index'),        'color'=>'#10b981', 'bg'=>'bg-emerald-50','icon'=>'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
                ['label'=>'Applications',  'sub'=>$pendingApprovals.' pending',        'href'=>route('admin.applications.index'),  'color'=>'#ef4444', 'bg'=>'bg-red-50',    'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['label'=>'Payments',      'sub'=>'Financial records',                'href'=>route('admin.payments.index'),       'color'=>'#0F6B3E', 'bg'=>'bg-emerald-50','icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['label'=>'Users',         'sub'=>number_format($totalUsers).' total', 'href'=>route('admin.users'),               'color'=>'#2563eb', 'bg'=>'bg-blue-50',   'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
            @endphp
            @foreach($modules as $m)
            <a href="{{ $m['href'] }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-col items-center text-center hover:shadow-md transition group">
                <div class="w-12 h-12 {{ $m['bg'] }} rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $m['color'] }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $m['icon'] }}"/></svg>
                </div>
                <div class="font-bold text-slate-800 text-sm">{{ $m['label'] }}</div>
                <div class="text-xs text-slate-400 mt-0.5">{{ $m['sub'] }}</div>
            </a>
            @endforeach
        </div>

        {{-- ── Order Operations + Consultations side by side ── --}}
        <div class="grid lg:grid-cols-2 gap-6">

            {{-- Orders --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="font-bold text-slate-800">Recent Orders</span>
                    <div class="flex gap-2 text-xs">
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full font-bold">{{ $ordersUnassigned }} unassigned</span>
                        <a href="{{ route('admin.orders.index') }}" class="text-[#0F6B3E] font-semibold hover:underline">All →</a>
                    </div>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($recentOrders as $order)
                    @php $badge = $order->riderStatusBadge(); @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs font-bold text-slate-700">₦{{ number_format($order->total, 0) }}</span>
                            @if($order->rider_id)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            @else
                            <a href="{{ route('admin.orders.show', $order) }}" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold hover:bg-red-200">Assign</a>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-slate-400 text-sm">No orders yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Consultations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="font-bold text-slate-800">Recent Consultations</span>
                    <div class="flex gap-2 text-xs">
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full font-bold">{{ $consultsUnassigned }} unassigned</span>
                        <a href="{{ route('admin.consultations.index') }}" class="text-[#0F6B3E] font-semibold hover:underline">All →</a>
                    </div>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($recentConsults as $c)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-semibold text-xs text-slate-700 truncate">{{ $c->farmer?->first_name }} {{ $c->farmer?->last_name }}</div>
                            <div class="text-xs text-slate-400 truncate">{{ Str::limit($c->symptoms, 40) }}</div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c->case_type==='crop'?'bg-emerald-100 text-emerald-700':'bg-purple-100 text-purple-700' }}">{{ ucfirst($c->case_type) }}</span>
                            @if(!$c->expert_id && $c->status==='open')
                            <a href="{{ route('admin.consultations.show', $c) }}" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold hover:bg-red-200">Assign</a>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c->status==='resolved'?'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">{{ ucfirst($c->status) }}</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-slate-400 text-sm">No consultations yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Operational Stats Row ── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            @foreach([
                ['l'=>'Orders Today',  'v'=>$ordersToday??0,         'c'=>'#2563eb'],
                ['l'=>'Pending',       'v'=>$ordersPending??0,       'c'=>'#f59e0b'],
                ['l'=>'Unassigned',    'v'=>$ordersUnassigned??0,    'c'=>'#ef4444'],
                ['l'=>'In Transit',    'v'=>$ordersInTransit??0,     'c'=>'#8b5cf6'],
                ['l'=>'Delivered',     'v'=>$ordersDelivered??0,     'c'=>'#10b981'],
                ['l'=>'Riders Free',   'v'=>$ridersAvailable??0,     'c'=>'#10b981'],
                ['l'=>'Consults Open', 'v'=>$consultsOpen??0,        'c'=>'#8b5cf6'],
                ['l'=>'Rev Today',     'v'=>'₦'.number_format($revenueToday??0), 'c'=>'#0F6B3E'],
            ] as $s)
            <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-100 text-center border-t-2" style="border-top-color:{{ $s['c'] }}">
                <div class="text-lg font-extrabold" style="color:{{ $s['c'] }}">{{ $s['v'] }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">{{ $s['l'] }}</div>
            </div>
            @endforeach
        </div>

    </div>
</x-app-layout>
