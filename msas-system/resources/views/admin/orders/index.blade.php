<x-app-layout>
    <x-slot name="header">Order Management</x-slot>

    <div class="space-y-6">

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            @foreach([
                ['label'=>'Total',       'val'=>$stats['total'],         'color'=>'#64748b'],
                ['label'=>'Pending',     'val'=>$stats['pending'],       'color'=>'#f59e0b'],
                ['label'=>'Unassigned',  'val'=>$stats['unassigned'],    'color'=>'#ef4444'],
                ['label'=>'In Transit',  'val'=>$stats['in_transit'],    'color'=>'#8b5cf6'],
                ['label'=>'Delivered',   'val'=>$stats['delivered'],     'color'=>'#10b981'],
                ['label'=>'Cancelled',   'val'=>$stats['cancelled'],     'color'=>'#dc2626'],
                ['label'=>"Today Rev",   'val'=>'₦'.number_format($stats['today_revenue']),  'color'=>'#0F6B3E'],
                ['label'=>'Total Rev',   'val'=>'₦'.number_format($stats['total_revenue']),  'color'=>'#0F6B3E'],
            ] as $s)
            <div class="bg-white rounded-xl p-3 shadow-sm border border-slate-100 border-l-4" style="border-left-color:{{ $s['color'] }}">
                <div class="text-lg font-extrabold" style="color:{{ $s['color'] }}">{{ $s['val'] }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Flash --}}
        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <input name="search" value="{{ request('search') }}" placeholder="Order number…"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-[160px]">
            <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                @foreach(['pending','confirmed','processing','shipped','delivered','cancelled','returned'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="rider_status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Rider States</option>
                <option value="unassigned" {{ request('rider_status')==='unassigned'?'selected':'' }}>Unassigned</option>
                @foreach(['assigned','accepted','declined','in_transit','completed','returned'] as $s)
                <option value="{{ $s }}" {{ request('rider_status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            @if(request()->hasAny(['search','status','rider_status']))
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Clear</a>
            @endif
        </form>

        {{-- Orders Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Buyer</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Seller</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Rider</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($orders as $order)
                        @php
                            $sc = ['pending'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-purple-100 text-purple-700','shipped'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700','returned'=>'bg-orange-100 text-orange-700'];
                            $badge = $order->riderStatusBadge();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-700 text-xs">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>
                                <div class="text-xs text-slate-400">{{ $order->buyer?->phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $order->dealer?->first_name }} {{ $order->dealer?->last_name }}</td>
                            <td class="px-4 py-3 font-bold text-slate-700 text-xs">₦{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->rider)
                                <div class="text-xs font-semibold text-slate-700">{{ $order->rider->first_name }} {{ $order->rider->last_name }}</div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                @else
                                <span class="text-xs text-red-500 font-semibold">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $order->created_at->format('M d, H:i') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    class="px-3 py-1 bg-[#0F6B3E] text-white text-xs rounded-lg font-bold hover:bg-[#047857]">
                                    Manage
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">No orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    </div>
</x-app-layout>
