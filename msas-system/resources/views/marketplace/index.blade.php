<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <span class="text-2xl">🛒</span> {{ __('Marketplace') }}
            </h2>
            <span class="text-sm text-slate-500 font-medium">
                {{ $items->total() }} {{ \Illuminate\Support\Str::plural('product', $items->total()) }} available
            </span>
        </div>
    </x-slot>

    {{-- ─── Search + Filter Bar ─── --}}
    <form method="GET" action="{{ route('marketplace') }}" class="mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search products, brands, or keywords…"
                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 transition">
            </div>
            <div class="relative min-w-[180px]">
                <select name="category"
                    class="w-full pl-4 pr-8 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 bg-white appearance-none transition">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </div>
            <button type="submit"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition shadow-sm shadow-emerald-200">
                Search
            </button>
            @if(request('search') || request('category'))
            <a href="{{ route('marketplace') }}"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-xl transition">
                Clear
            </a>
            @endif
        </div>
    </form>

    {{-- ─── Category Chips ─── --}}
    @if($categories->count())
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('marketplace', array_merge(request()->except(['category','page']))) }}"
            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition
                {{ !request('category') ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-400 hover:text-emerald-700' }}">
            All
        </a>
        @foreach($categories as $cat)
        <a href="{{ route('marketplace', array_merge(request()->except(['category','page']), ['category' => $cat])) }}"
            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition
                {{ request('category') === $cat ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-400 hover:text-emerald-700' }}">
            {{ $cat }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ─── Product Grid ─── --}}
    @if($items->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
        @foreach($items as $product)
        @php
            $stockClass = match($product->stock_status) {
                'low_stock'    => 'bg-amber-100 text-amber-700 border-amber-200',
                'out_of_stock' => 'bg-red-100 text-red-700 border-red-200',
                default        => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            };
            $stockLabel = match($product->stock_status) {
                'low_stock'    => 'Low Stock',
                'out_of_stock' => 'Out of Stock',
                default        => 'In Stock',
            };
            $dealerName  = $product->dealer
                ? trim($product->dealer->first_name . ' ' . $product->dealer->last_name)
                : 'MSAS Dealer';
            $dealerPhone = $product->dealer?->phone ?? '';
            $priceLabel  = '₦' . number_format($product->selling_price, 2) . ($product->unit ? ' / ' . $product->unit : '');
        @endphp
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:border-emerald-200 transition-all duration-200">
            {{-- Image --}}
            <div class="relative aspect-square bg-slate-50 overflow-hidden">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ e($product->name) }}"
                        loading="lazy"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-5xl text-slate-200 bg-slate-50">📦</div>
                @endif
                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $stockClass }}">
                    {{ $stockLabel }}
                </span>
                @if($product->is_featured)
                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-white">
                    ⭐ Featured
                </span>
                @endif
            </div>

            {{-- Info --}}
            <div class="p-3 md:p-4 flex flex-col flex-1 gap-1">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">{{ $product->category }}</span>
                <h3 class="text-sm md:text-[0.875rem] font-bold text-slate-800 leading-snug line-clamp-2 flex-1">
                    {{ $product->name }}
                </h3>
                @if($product->brand)
                <p class="text-[11px] text-slate-400">{{ $product->brand }}</p>
                @endif

                <div class="flex items-baseline gap-1 flex-wrap pt-1">
                    <span class="text-base font-extrabold text-emerald-700">₦{{ number_format($product->selling_price, 2) }}</span>
                    @if($product->unit)
                    <span class="text-[11px] text-slate-400">/ {{ $product->unit }}</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400">{{ number_format($product->quantity_in_stock) }} units left</p>

                <div class="mt-2 flex gap-1.5">
                    <a href="{{ route('marketplace.show', $product) }}"
                        class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Buy
                    </a>
                    <button
                        data-name="{{ $product->name }}"
                        data-price="{{ $priceLabel }}"
                        data-dealer="{{ $dealerName }}"
                        data-phone="{{ $dealerPhone }}"
                        data-category="{{ $product->category }}"
                        onclick="openContactModal({name:this.dataset.name,price:this.dataset.price,dealer:this.dataset.dealer,phone:this.dataset.phone,category:this.dataset.category})"
                        class="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1 shrink-0" title="Contact Dealer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 md:p-16 text-center">
            <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-5 border border-emerald-100">🛒</div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">
                @if(request('search') || request('category'))No Products Found
                @else Marketplace Coming Soon @endif
            </h3>
            <p class="text-slate-500 max-w-sm mx-auto text-sm leading-relaxed">
                @if(request('search'))
                    No products match "<strong>{{ e(request('search')) }}</strong>". Try a different keyword or category.
                @elseif(request('category'))
                    No products in <strong>{{ request('category') }}</strong> right now. Dealers are adding more — check back soon!
                @else
                    Our marketplace is being stocked by trusted agro-dealers. Products will appear here once verified and approved.
                @endif
            </p>
            @if(request('search') || request('category'))
            <a href="{{ route('marketplace') }}"
                class="inline-block mt-5 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition">
                Browse All Products
            </a>
            @endif
        </div>
    @endif

    {{-- ─── Pagination ─── --}}
    @if($items->hasPages())
    <div class="mt-8 flex justify-center">
        {{ $items->onEachSide(1)->links() }}
    </div>
    @endif

    {{-- ─── Contact Dealer Modal ─── --}}
    <div id="contactModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden"
        aria-modal="true" role="dialog" aria-labelledby="modal-title">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeContactModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10" style="animation:modal-in .2s ease-out">
            <button onclick="closeContactModal()"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition"
                aria-label="Close dialog">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="text-center mb-5">
                <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-3">📦</div>
                <h3 id="modal-title" class="text-lg font-extrabold text-slate-800"></h3>
                <p id="modal-category" class="text-xs text-emerald-600 font-bold uppercase tracking-wider mt-0.5"></p>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 mb-5 space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Price</span>
                    <span id="modal-price" class="font-extrabold text-emerald-700 text-base"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Dealer</span>
                    <span id="modal-dealer" class="font-semibold text-slate-700"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Phone</span>
                    <span id="modal-phone" class="font-semibold text-slate-700"></span>
                </div>
            </div>

            <div class="space-y-2.5">
                <a id="modal-whatsapp-btn" href="#" target="_blank" rel="noopener noreferrer"
                    class="w-full flex items-center justify-center gap-2.5 py-3 bg-[#25D366] hover:bg-[#1ebe5d] text-white font-bold rounded-xl transition text-sm">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Chat on WhatsApp
                </a>
                <a id="modal-call-btn" href="#"
                    class="w-full flex items-center justify-center gap-2.5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Call Dealer
                </a>
            </div>

            <p class="text-[11px] text-slate-400 text-center mt-4 leading-relaxed">
                Contact the dealer directly to confirm availability, negotiate, and arrange delivery.
            </p>
        </div>
    </div>

    <style>
    @@keyframes modal-in {
        from { opacity:0; transform:scale(.95) translateY(8px); }
        to   { opacity:1; transform:scale(1) translateY(0); }
    }
    </style>
    <script>
    (function(){
        function openContactModal(data) {
            document.getElementById('modal-title').textContent    = data.name;
            document.getElementById('modal-category').textContent = data.category;
            document.getElementById('modal-price').textContent    = data.price;
            document.getElementById('modal-dealer').textContent   = data.dealer;

            var raw = (data.phone || '').replace(/\D/g,'');
            if(raw.length === 11 && raw.startsWith('0')) raw = '234' + raw.slice(1);

            document.getElementById('modal-phone').textContent = data.phone || '—';

            var msg = encodeURIComponent(
                "Hi, I'm interested in ordering *" + data.name + "* (" + data.price + ") from MSAS Marketplace. Is it still available?"
            );

            var waBtn   = document.getElementById('modal-whatsapp-btn');
            var callBtn = document.getElementById('modal-call-btn');

            if(raw) {
                waBtn.href   = 'https://wa.me/' + raw + '?text=' + msg;
                callBtn.href = 'tel:+' + raw;
                callBtn.classList.remove('hidden');
            } else {
                waBtn.href = '#';
                callBtn.classList.add('hidden');
            }

            document.getElementById('contactModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        window.openContactModal = openContactModal;

        window.closeContactModal = function() {
            document.getElementById('contactModal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        document.addEventListener('keydown', function(e){ if(e.key==='Escape') window.closeContactModal(); });
    })();
    </script>
</x-app-layout>
