<x-app-layout>
    <x-slot name="header">Shopping Cart</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-extrabold text-slate-800">Your Cart</h2>
            <a href="{{ route('marketplace') }}" class="text-sm text-[#0F6B3E] font-semibold hover:underline">← Continue Shopping</a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
        @endif

        @if($items->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm py-16 text-center">
            <div class="text-5xl mb-4">🛒</div>
            <h3 class="font-extrabold text-slate-700 text-lg mb-2">Your cart is empty</h3>
            <p class="text-slate-400 text-sm mb-5">Browse the marketplace to find products.</p>
            <a href="{{ route('marketplace') }}" class="px-6 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">
                Shop Now
            </a>
        </div>
        @else

        {{-- Cart Items --}}
        <form method="POST" action="{{ route('marketplace.cart.update') }}">
            @csrf
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                @foreach($items as $item)
                @php $p = $item['product']; @endphp
                <div class="flex items-center gap-4 p-4">
                    <div class="w-16 h-16 rounded-xl bg-slate-50 flex items-center justify-center text-2xl flex-shrink-0 overflow-hidden border border-slate-100">
                        @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                        @else 📦 @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('marketplace.show', $p) }}" class="font-bold text-slate-800 text-sm hover:underline line-clamp-1">{{ $p->name }}</a>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $p->category }} · {{ $p->dealer?->first_name }} {{ $p->dealer?->last_name }}</p>
                        <p class="text-sm font-extrabold text-[#0F6B3E] mt-1">₦{{ number_format($p->selling_price, 2) }} / {{ $p->unit }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <input type="number" name="quantities[{{ $p->id }}]" value="{{ $item['quantity'] }}"
                            min="0" max="{{ $p->quantity_in_stock }}"
                            class="w-20 text-center border border-slate-200 rounded-xl px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div class="text-right flex-shrink-0 w-24">
                        <p class="font-bold text-slate-800">₦{{ number_format($item['subtotal'], 2) }}</p>
                    </div>
                    <form method="POST" action="{{ route('marketplace.cart.remove', $p->id) }}" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-semibold" title="Remove">✕</button>
                    </form>
                </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    Update Cart
                </button>
            </div>
        </form>

        {{-- Summary + Checkout --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-extrabold text-slate-800 border-b pb-3">Order Summary</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal ({{ $items->count() }} items)</span>
                    <span class="font-semibold">₦{{ number_format($total, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>VAT (7.5%)</span>
                    <span class="font-semibold">₦{{ number_format($total * 0.075, 2) }}</span>
                </div>
                <div class="flex justify-between font-extrabold text-slate-800 text-base pt-2 border-t border-slate-100">
                    <span>Total</span>
                    <span class="text-[#0F6B3E]">₦{{ number_format($total * 1.075, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('marketplace.checkout') }}" class="block w-full py-3 bg-[#0F6B3E] text-white text-center rounded-xl font-bold text-sm hover:bg-[#047857] transition shadow-sm">
                Proceed to Checkout
            </a>
            <form method="POST" action="{{ route('marketplace.cart.clear') }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-red-500 transition" onclick="return confirm('Clear entire cart?')">Clear Cart</button>
            </form>
        </div>
        @endif

    </div>
</x-app-layout>
