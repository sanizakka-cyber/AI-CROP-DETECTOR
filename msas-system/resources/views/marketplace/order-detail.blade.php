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

        {{-- Status Tracker --}}
        @php
            $steps  = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
            $curIdx = array_search($order->status, $steps);
            $cancelled = $order->status === 'cancelled';
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-extrabold text-slate-800 mb-5 border-b pb-3">
                Order Status:
                <span class="ml-2 px-3 py-1 rounded-full text-xs font-bold
                    {{ $cancelled ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ ucfirst($order->status) }}
                </span>
            </h3>
            @if(!$cancelled)
            <div class="flex items-center justify-between">
                @foreach($steps as $i => $step)
                <div class="flex flex-col items-center flex-1 {{ $i < count($steps)-1 ? 'relative' : '' }}">
                    @if($i < count($steps)-1)
                    <div class="absolute top-4 left-1/2 w-full h-0.5 {{ $i < $curIdx ? 'bg-[#0F6B3E]' : 'bg-slate-200' }} z-0"></div>
                    @endif
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 relative
                        {{ $i <= $curIdx ? 'bg-[#0F6B3E] text-white' : 'bg-slate-100 text-slate-400' }}">
                        {{ $i < $curIdx ? '✓' : ($i + 1) }}
                    </div>
                    <p class="text-[10px] font-semibold mt-1 text-center
                        {{ $i <= $curIdx ? 'text-[#0F6B3E]' : 'text-slate-400' }}">
                        {{ ucfirst($step) }}
                    </p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-red-600 font-medium">This order has been cancelled.</p>
            @endif
        </div>

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
