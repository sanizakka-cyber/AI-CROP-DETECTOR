<x-app-layout>
    <x-slot name="header">Order {{ $order->order_number }}</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">{{ $order->order_number }}</h2>
                <p class="text-slate-400 text-xs mt-0.5">Placed {{ $order->created_at->format('M d, Y @ g:i A') }}</p>
            </div>
            <a href="{{ route('marketplace.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                ← My Orders
            </a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium">✓ {{ session('success') }}</div>
        @endif

        {{-- Status Tracker (full delivery timeline) --}}
        @php
            $timeline = $order->orderTimeline();
            $cancelled = $order->status === 'cancelled';
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-extrabold text-slate-800 mb-5 border-b pb-3 flex items-center gap-2">
                Order Status:
                <span class="px-3 py-1 rounded-full text-xs font-bold
                    {{ $cancelled ? 'bg-red-100 text-red-700' : ($order->rider_status==='awaiting_confirmation' ? 'bg-amber-100 text-amber-700' : ($order->status==='completed' ? 'bg-green-100 text-green-700' : 'bg-emerald-100 text-emerald-700')) }}">
                    {{ $order->rider_status === 'awaiting_confirmation' ? 'Awaiting Your Confirmation' : ucfirst($order->status) }}
                </span>
            </h3>
            @if(!$cancelled)
            <div class="flex items-start justify-between gap-1">
                @foreach($timeline as $i => $step)
                <div class="flex flex-col items-center flex-1 relative">
                    @if($i < count($timeline)-1)
                    <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $step['done'] && ($timeline[$i+1]['done'] ?? false) ? 'bg-[#0F6B3E]' : 'bg-slate-200' }} z-0"></div>
                    @endif
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 relative
                        {{ $step['done'] ? 'bg-[#0F6B3E] text-white' : 'bg-slate-100 text-slate-400' }}">
                        {{ $step['done'] ? '✓' : ($i+1) }}
                    </div>
                    <p class="text-[10px] font-semibold mt-1 text-center {{ $step['done'] ? 'text-[#0F6B3E]' : 'text-slate-400' }}">
                        {{ $step['label'] }}
                    </p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-red-600 font-medium">This order has been cancelled.</p>
            @endif
        </div>

        {{-- Rider info card (shown after rider accepts) --}}
        @if($order->rider && in_array($order->rider_status, ['accepted','in_transit','awaiting_confirmation','completed']))
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5">
            <h3 class="font-bold text-indigo-700 text-sm uppercase tracking-wide mb-3">Your Rider</h3>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="font-extrabold text-slate-800">{{ $order->rider->first_name }} {{ $order->rider->last_name }}</div>
                    <div class="text-sm text-slate-500 mt-0.5">{{ ucfirst($order->rider->vehicle_type ?? 'motorcycle') }}</div>
                    @if($order->rider->rider_rating)
                    <div class="text-xs text-amber-600 mt-0.5">★ {{ number_format($order->rider->rider_rating, 1) }}</div>
                    @endif
                </div>
                @if($order->rider->phone)
                <a href="tel:{{ $order->rider->phone }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Call Rider
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Customer Confirmation Panel --}}
        @if($order->rider_status === 'awaiting_confirmation')
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-6">
            <h3 class="font-extrabold text-amber-800 text-lg mb-1">Your Order Has Arrived!</h3>
            <p class="text-amber-700 text-sm mb-5">Please confirm that you received your order in good condition, or report an issue if there's a problem.</p>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('marketplace.orders.confirm', $order) }}">
                    @csrf
                    <button class="px-6 py-3 bg-[#0F6B3E] text-white rounded-xl font-bold hover:bg-[#047857] transition">
                        Confirm Delivery
                    </button>
                </form>
                <button onclick="document.getElementById('issue-form').classList.toggle('hidden')"
                        class="px-6 py-3 bg-red-100 text-red-700 rounded-xl font-bold hover:bg-red-200 transition">
                    Report Issue
                </button>
            </div>
            <form id="issue-form" method="POST" action="{{ route('marketplace.orders.report', $order) }}" class="hidden mt-4">
                @csrf
                <textarea name="issue" rows="3" required placeholder="Describe the issue…"
                    class="w-full border border-red-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-400 resize-none"></textarea>
                <button class="mt-2 px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700">Submit Report</button>
            </form>
        </div>
        @endif

        @if($order->buyer_confirmed_at)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-extrabold text-lg flex-shrink-0">✓</div>
            <div>
                <div class="font-bold text-green-800">Delivery Confirmed</div>
                <div class="text-xs text-green-600">You confirmed this order on {{ $order->buyer_confirmed_at->format('M d, Y @ g:i A') }}</div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Seller Info --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-extrabold text-slate-800 mb-3 border-b pb-2">Seller</h3>
                @if($order->dealer)
                <p class="font-semibold text-slate-700">{{ $order->dealer->first_name }} {{ $order->dealer->last_name }}</p>
                @if($order->dealer->phone)
                <p class="text-sm text-slate-400 mt-1">📞 {{ $order->dealer->phone }}</p>
                @endif
                @if($order->dealer->email)
                <p class="text-sm text-slate-400 mt-0.5">✉ {{ $order->dealer->email }}</p>
                @endif
                @else
                <p class="text-sm text-slate-400">Seller info not available.</p>
                @endif
            </div>

            {{-- Delivery Info --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-extrabold text-slate-800 mb-3 border-b pb-2">Delivery</h3>
                <p class="text-sm text-slate-700">{{ $order->delivery_address }}</p>
                @if($order->delivery_notes)
                <p class="text-xs text-slate-400 mt-2 italic">Note: {{ $order->delivery_notes }}</p>
                @endif
                @if($order->delivered_at)
                <p class="text-xs text-emerald-700 font-semibold mt-2">Delivered {{ $order->delivered_at->format('M d, Y') }}</p>
                @endif
            </div>

        </div>

        {{-- Items --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-extrabold text-slate-800">Order Items</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs font-bold text-slate-500 uppercase text-left">
                        <th class="px-5 py-3">Product</th>
                        <th class="px-4 py-3">Unit Price</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                    <tr>
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-700">{{ $item->product_name }}</p>
                            @if($item->unit)<p class="text-xs text-slate-400">{{ $item->unit }}</p>@endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">₦{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->quantity }}</td>
                        <td class="px-5 py-3 text-right font-bold text-slate-800">₦{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 border-t border-slate-100">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right text-sm text-slate-500 font-medium">Subtotal</td>
                        <td class="px-5 py-3 text-right font-semibold text-slate-700">₦{{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-2 text-right text-sm text-slate-500 font-medium">VAT (7.5%)</td>
                        <td class="px-5 py-2 text-right font-semibold text-slate-700">₦{{ number_format($order->tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right font-extrabold text-slate-800">Total</td>
                        <td class="px-5 py-3 text-right font-extrabold text-[#0F6B3E] text-base">₦{{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('marketplace') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">
                Continue Shopping
            </a>
            <a href="{{ route('marketplace.orders') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                All Orders
            </a>
        </div>

    </div>
</x-app-layout>
