<x-app-layout>
    <x-slot name="header">Order Oversight</x-slot>

    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            @foreach([
                ['label'=>'Total',      'val'=>$stats['total'],         'color'=>'#64748b'],
                ['label'=>'Pending',    'val'=>$stats['pending'],       'color'=>'#f59e0b'],
                ['label'=>'Unassigned', 'val'=>$stats['unassigned'],    'color'=>'#ef4444'],
                ['label'=>'In Transit', 'val'=>$stats['in_transit'],    'color'=>'#8b5cf6'],
                ['label'=>'Delivered',  'val'=>$stats['delivered'],     'color'=>'#10b981'],
                ['label'=>'Cancelled',  'val'=>$stats['cancelled'],     'color'=>'#dc2626'],
                ['label'=>'Revenue',    'val'=>'₦'.number_format($stats['revenue']), 'color'=>'#0F6B3E'],
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
            <input name="search" value="{{ request('search') }}" placeholder="Order number or buyer…"
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
                @foreach(['assigned','accepted','in_transit','completed','declined'] as $s)
                <option value="{{ $s }}" {{ request('rider_status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            @if(request()->hasAny(['search','status','rider_status']))
            <a href="{{ route('ceo.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Clear</a>
            @endif
        </form>

        {{-- Table --}}
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
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Quick Assign</th>
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
                            <td class="px-4 py-3 font-bold text-xs text-slate-700">₦{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($order->status) }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                @if($order->rider)
                                {{ $order->rider->first_name }} {{ $order->rider->last_name }}
                                @else
                                <span class="text-red-500 font-semibold">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if(!in_array($order->rider_status ?? '', ['in_transit','completed']) && !in_array($order->status, ['delivered','cancelled','returned']))
                                <form method="POST" action="{{ route('ceo.orders.assign', $order) }}" class="flex gap-2">
                                    @csrf
                                    <select name="rider_id" required class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs">
                                        <option value="">Select…</option>
                                        @foreach($riders as $r)
                                        <option value="{{ $r->id }}" {{ $order->rider_id===$r->id?'disabled':'' }}>{{ $r->first_name }} {{ $r->last_name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="px-2 py-1.5 bg-[#0F6B3E] text-white text-xs rounded-lg font-bold hover:bg-[#047857]">Assign</button>
                                </form>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm">No orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    </div>
</x-app-layout>
