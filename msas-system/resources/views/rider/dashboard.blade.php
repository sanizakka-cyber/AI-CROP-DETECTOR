<x-app-layout>
    <x-slot name="header">Rider Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Flash --}}
        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        {{-- Status toggle + greeting --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-slate-800 text-lg">Welcome, {{ $rider->first_name }}</h2>
                <p class="text-slate-500 text-sm mt-0.5">Manage your active deliveries below.</p>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $rs = $rider->rider_status ?? 'offline';
                    $rc = ['available'=>'bg-green-100 text-green-700','busy'=>'bg-amber-100 text-amber-700','offline'=>'bg-slate-100 text-slate-500'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $rc[$rs] ?? 'bg-slate-100 text-slate-500' }}">{{ ucfirst($rs) }}</span>
                <form method="POST" action="{{ route('rider.status') }}" class="flex gap-2 items-center">
                    @csrf @method('PATCH')
                    <select name="rider_status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="available" {{ $rs==='available'?'selected':'' }}>Available</option>
                        <option value="offline" {{ $rs==='offline'?'selected':'' }}>Offline</option>
                    </select>
                    <button class="px-3 py-2 bg-[#0F6B3E] text-white text-sm rounded-lg font-bold hover:bg-[#047857]">Set</button>
                </form>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @foreach([
                ['label'=>'New',        'val'=>$stats['assigned'],  'color'=>'#f59e0b'],
                ['label'=>'In Transit', 'val'=>$stats['in_transit'],'color'=>'#8b5cf6'],
                ['label'=>'Completed',  'val'=>$stats['completed'], 'color'=>'#10b981'],
                ['label'=>'Declined',   'val'=>$stats['declined'],  'color'=>'#ef4444'],
                ['label'=>'Est. Earnings (5%)', 'val'=>'₦'.number_format($stats['earnings'],2), 'color'=>'#0F6B3E'],
            ] as $s)
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100 border-l-4" style="border-left-color:{{ $s['color'] }}">
                <div class="text-xl font-extrabold" style="color:{{ $s['color'] }}">{{ $s['val'] }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Active Orders --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <span class="font-bold text-slate-700">Active Orders</span>
                <a href="{{ route('rider.orders') }}" class="text-xs text-[#0F6B3E] font-semibold hover:underline">View All →</a>
            </div>

            @forelse($activeOrders as $order)
            @php
                $rs = $order->rider_status;
                $rc = ['assigned'=>'bg-amber-100 text-amber-700','accepted'=>'bg-blue-100 text-blue-700','in_transit'=>'bg-purple-100 text-purple-700'];
                $mapQuery = urlencode($order->delivery_address ?? '');
            @endphp
            <div class="p-5 border-b border-slate-50 last:border-0">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rc[$rs] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst(str_replace('_',' ',$rs)) }}</span>
                        </div>
                        <div class="font-semibold text-slate-800 mt-2">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>

                        {{-- Contact info (shown after acceptance) --}}
                        @if(in_array($rs, ['accepted','in_transit']))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if($order->buyer?->phone)
                            <a href="tel:{{ $order->buyer->phone }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 text-xs font-bold rounded-lg hover:bg-green-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Call Customer
                            </a>
                            @endif
                            @if($order->delivery_address)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold rounded-lg hover:bg-blue-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Navigate
                            </a>
                            @endif
                        </div>
                        @endif

                        <div class="text-sm text-slate-500 mt-2">{{ $order->delivery_address ?? 'No address provided' }}</div>
                        <div class="font-bold text-[#0F6B3E] mt-1">₦{{ number_format($order->total, 2) }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mt-4">
                    @if($rs === 'assigned')
                    <form method="POST" action="{{ route('rider.orders.accept', $order) }}">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg font-bold hover:bg-green-700">Accept Delivery</button>
                    </form>
                    <form method="POST" action="{{ route('rider.orders.decline', $order) }}" onsubmit="return confirm('Decline this order?')">
                        @csrf
                        <input type="hidden" name="reason" value="Rider unavailable">
                        <button class="px-4 py-2 bg-red-100 text-red-700 text-sm rounded-lg font-bold hover:bg-red-200">Decline</button>
                    </form>
                    @elseif($rs === 'accepted')
                    <form method="POST" action="{{ route('rider.orders.transit', $order) }}">
                        @csrf
                        <button class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg font-bold hover:bg-purple-700">Start Delivery</button>
                    </form>
                    @elseif($rs === 'in_transit')
                    <form method="POST" action="{{ route('rider.orders.delivered', $order) }}" onsubmit="return confirm('Confirm you have delivered this order?')">
                        @csrf
                        <button class="px-4 py-2 bg-[#0F6B3E] text-white text-sm rounded-lg font-bold hover:bg-[#047857]">Mark Delivered</button>
                    </form>
                    @endif

                    <a href="{{ route('rider.orders.show', $order) }}" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm rounded-lg font-bold hover:bg-slate-200">View Details</a>
                </div>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-slate-400 text-sm">No active orders. You're all caught up!</div>
            @endforelse
        </div>

    </div>
</x-app-layout>
