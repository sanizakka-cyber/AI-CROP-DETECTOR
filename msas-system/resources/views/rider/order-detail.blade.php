<x-app-layout>
    <x-slot name="header">Order {{ $order->order_number }}</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">

        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        <a href="{{ route('rider.orders') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">← My Deliveries</a>

        {{-- Status header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            @php
                $rs = $order->rider_status ?? 'unassigned';
                $rc = ['assigned'=>'bg-amber-100 text-amber-700','accepted'=>'bg-blue-100 text-blue-700','in_transit'=>'bg-purple-100 text-purple-700','completed'=>'bg-green-100 text-green-700','declined'=>'bg-red-100 text-red-700'];
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-mono text-xs font-bold text-slate-500">{{ $order->order_number }}</div>
                    <div class="text-lg font-extrabold text-slate-800 mt-0.5">Delivery Detail</div>
                </div>
                <span class="px-3 py-1.5 rounded-full text-xs font-bold {{ $rc[$rs] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ ucfirst(str_replace('_',' ',$rs)) }}
                </span>
            </div>
        </div>

        {{-- Delivery info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Delivery Info</h3>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase mb-1">Customer</div>
                <div class="font-semibold text-slate-800">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>
                <a href="tel:{{ $order->buyer?->phone }}" class="text-[#0F6B3E] font-semibold text-sm">{{ $order->buyer?->phone }}</a>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase mb-1">Delivery Address</div>
                <div class="text-sm text-slate-700">{{ $order->delivery_address ?? 'No address provided' }}</div>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase mb-1">Pickup From</div>
                <div class="font-semibold text-slate-800">{{ $order->dealer?->first_name }} {{ $order->dealer?->last_name }}</div>
                <div class="text-sm text-slate-500">{{ $order->dealer?->phone }}</div>
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Items</h3>
            <div class="space-y-2">
                @foreach($order->items as $item)
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">{{ $item->product_name }} × {{ $item->quantity }}</span>
                    <span class="font-semibold text-slate-700">₦{{ number_format($item->total, 2) }}</span>
                </div>
                @endforeach
                <div class="border-t border-slate-100 pt-2 flex justify-between font-bold">
                    <span class="text-slate-800">Total</span>
                    <span class="text-[#0F6B3E]">₦{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @if($rs === 'assigned')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-3">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Actions</h3>
            <form method="POST" action="{{ route('rider.orders.accept', $order) }}">
                @csrf
                <button class="w-full py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700">Accept This Delivery</button>
            </form>
            <form method="POST" action="{{ route('rider.orders.decline', $order) }}" onsubmit="return confirm('Decline this delivery?')">
                @csrf
                <div class="mb-2">
                    <input type="text" name="reason" placeholder="Reason for declining (optional)" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <button class="w-full py-3 bg-red-100 text-red-700 rounded-xl font-bold hover:bg-red-200">Decline</button>
            </form>
        </div>
        @elseif($rs === 'accepted')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Actions</h3>
            <form method="POST" action="{{ route('rider.orders.transit', $order) }}">
                @csrf
                <button class="w-full py-3 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700">I've Picked Up — Mark In Transit</button>
            </form>
        </div>
        @elseif($rs === 'in_transit')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Confirm Delivery</h3>
            <form method="POST" action="{{ route('rider.orders.delivered', $order) }}" enctype="multipart/form-data"
                  onsubmit="return confirm('Confirm that this order has been delivered?')">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Proof of Delivery Photo <span class="text-slate-300 font-normal">(optional)</span></label>
                    <input type="file" name="proof" accept="image/*" capture="environment"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <p class="text-xs text-slate-400 mt-1">Take a photo showing the package at the delivery address. Max 5 MB.</p>
                </div>
                <button class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold hover:bg-[#047857]">Mark as Delivered</button>
            </form>
        </div>
        @endif

    </div>
</x-app-layout>
