<x-app-layout>
    <x-slot name="header">{{ $product->name }}</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Breadcrumb --}}
        <nav class="text-xs text-slate-400 flex items-center gap-1.5">
            <a href="{{ route('marketplace') }}" class="hover:text-[#0F6B3E]">Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace', ['category' => $product->category]) }}" class="hover:text-[#0F6B3E]">{{ $product->category }}</a>
            <span>/</span>
            <span class="text-slate-600 font-medium">{{ $product->name }}</span>
        </nav>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
            ✓ {{ session('success') }}
            <a href="{{ route('marketplace.cart') }}" class="ml-auto font-bold underline">View Cart ({{ $cartCount }})</a>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            {{-- Product Image --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden aspect-square flex items-center justify-center">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <span class="text-8xl text-slate-200">📦</span>
                @endif
            </div>

            {{-- Product Info + Buy --}}
            <div class="space-y-5">
                <div>
                    <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold mb-2">{{ $product->category }}</span>
                    <h1 class="text-2xl font-extrabold text-slate-800 leading-tight">{{ $product->name }}</h1>
                    @if($product->brand)
                    <p class="text-sm text-slate-400 mt-1">by {{ $product->brand }}</p>
                    @endif
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-[#0F6B3E]">₦{{ number_format($product->selling_price, 2) }}</span>
                    @if($product->unit)
                    <span class="text-slate-400 text-sm">/ {{ $product->unit }}</span>
                    @endif
                </div>

                {{-- Stock badge --}}
                @php
                    $stockColors = ['in_stock'=>'bg-emerald-100 text-emerald-700','low_stock'=>'bg-amber-100 text-amber-700','out_of_stock'=>'bg-red-100 text-red-700'];
                    $stockLabels = ['in_stock'=>'In Stock','low_stock'=>'Low Stock','out_of_stock'=>'Out of Stock'];
                    $ss = $product->stock_status;
                @endphp
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $stockColors[$ss] }}">
                    {{ $stockLabels[$ss] }} ({{ number_format($product->quantity_in_stock) }} available)
                </span>

                {{-- Seller --}}
                @if($product->dealer)
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Sold by</p>
                    <p class="font-bold text-slate-700">{{ $product->dealer->first_name }} {{ $product->dealer->last_name }}</p>
                    @if($product->dealer->state)
                    <p class="text-xs text-slate-400 mt-0.5">📍 {{ $product->dealer->state }}</p>
                    @endif
                </div>
                @endif

                {{-- Add to Cart --}}
                @if($product->stock_status !== 'out_of_stock')
                <form method="POST" action="{{ route('marketplace.cart.add') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-semibold text-slate-700">Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->quantity_in_stock }}"
                            class="w-24 border border-slate-200 rounded-xl px-3 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <button type="submit" class="w-full py-3 bg-[#0F6B3E] text-white rounded-xl font-bold text-sm hover:bg-[#047857] transition shadow-sm">
                        Add to Cart
                    </button>
                </form>
                @else
                <button disabled class="w-full py-3 bg-slate-200 text-slate-400 rounded-xl font-bold text-sm cursor-not-allowed">
                    Out of Stock
                </button>
                @endif

                <a href="{{ route('marketplace.cart') }}" class="block text-center text-sm text-[#0F6B3E] font-semibold hover:underline">
                    View Cart ({{ $cartCount }} items)
                </a>
            </div>
        </div>

        {{-- Description / Details --}}
        @if($product->description || $product->usage_instructions || $product->storage_requirements)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="font-extrabold text-slate-800 text-lg border-b pb-3">Product Details</h2>
            @if($product->description)
            <div>
                <h3 class="text-sm font-bold text-slate-500 uppercase mb-1">Description</h3>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $product->description }}</p>
            </div>
            @endif
            @if($product->usage_instructions)
            <div>
                <h3 class="text-sm font-bold text-slate-500 uppercase mb-1">Usage Instructions</h3>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $product->usage_instructions }}</p>
            </div>
            @endif
            @if($product->storage_requirements)
            <div>
                <h3 class="text-sm font-bold text-slate-500 uppercase mb-1">Storage</h3>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $product->storage_requirements }}</p>
            </div>
            @endif
            @if($product->expiry_date)
            <div>
                <h3 class="text-sm font-bold text-slate-500 uppercase mb-1">Expiry Date</h3>
                <p class="text-sm text-slate-700">{{ $product->expiry_date->format('M d, Y') }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Related Products --}}
        @if($related->count())
        <div class="space-y-4">
            <h2 class="font-extrabold text-slate-800 text-lg">More in {{ $product->category }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($related as $r)
                <a href="{{ route('marketplace.show', $r) }}"
                   class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition flex flex-col">
                    <div class="aspect-square bg-slate-50 flex items-center justify-center text-4xl text-slate-200">
                        @if($r->image_url)
                        <img src="{{ $r->image_url }}" alt="{{ $r->name }}" class="w-full h-full object-cover">
                        @else 📦 @endif
                    </div>
                    <div class="p-3">
                        <p class="text-xs font-semibold text-slate-800 line-clamp-2">{{ $r->name }}</p>
                        <p class="text-sm font-extrabold text-[#0F6B3E] mt-1">₦{{ number_format($r->selling_price) }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
