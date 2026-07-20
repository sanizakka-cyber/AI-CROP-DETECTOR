<x-app-layout>
    <x-slot name="header">Checkout</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-extrabold text-slate-800">Checkout</h2>
            <a href="{{ route('marketplace.cart') }}" class="text-sm text-slate-500 hover:text-slate-700">← Back to Cart</a>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

            {{-- Delivery Form --}}
            <div class="md:col-span-3 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h3 class="font-extrabold text-slate-800 mb-4 border-b pb-3">Delivery Information</h3>
                    <form id="checkoutForm" method="POST" action="{{ route('marketplace.order.place') }}" class="space-y-4">
                        @csrf

                        {{-- Pre-fill from user profile --}}
                        @php $u = auth()->user(); @endphp
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                            <input type="text" value="{{ $u->first_name }} {{ $u->last_name }}" disabled
                                class="w-full border border-slate-100 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                            <input type="text" value="{{ $u->phone ?? '' }}" disabled
                                class="w-full border border-slate-100 bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Delivery Address *</label>
                            <textarea name="delivery_address" required rows="3"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30 resize-none"
                                placeholder="Full delivery address including LGA and State…">{{ old('delivery_address', $u->address ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Delivery Notes (optional)</label>
                            <textarea name="delivery_notes" rows="2"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30 resize-none"
                                placeholder="Any special instructions for delivery…">{{ old('delivery_notes') }}</textarea>
                        </div>
                    </form>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    <p class="font-bold mb-1">💡 Payment Note</p>
                    <p>Orders are currently placed on a pay-on-delivery basis. The seller will contact you to confirm and arrange delivery.</p>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="md:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h3 class="font-extrabold text-slate-800 mb-3 border-b pb-2">Order Summary</h3>
                    <div class="space-y-3 mb-4">
                        @foreach($items as $item)
                        @php $p = $item['product']; @endphp
                        <div class="flex justify-between items-start text-sm gap-2">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-700 line-clamp-1">{{ $p->name }}</p>
                                <p class="text-xs text-slate-400">x{{ $item['quantity'] }} · {{ $p->unit }}</p>
                            </div>
                            <span class="font-semibold text-slate-700 shrink-0">₦{{ number_format($item['subtotal'], 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="space-y-1.5 text-sm border-t pt-3">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span class="font-medium">₦{{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>VAT (7.5%)</span>
                            <span class="font-medium">₦{{ number_format($tax, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-extrabold text-slate-800 text-base pt-1.5 border-t border-slate-100">
                            <span>Total</span>
                            <span class="text-[#0F6B3E]">₦{{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <button form="checkoutForm" type="submit"
                    class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold text-sm hover:bg-[#047857] transition shadow-sm">
                    Place Order
                </button>
            </div>

        </div>
    </div>
</x-app-layout>
