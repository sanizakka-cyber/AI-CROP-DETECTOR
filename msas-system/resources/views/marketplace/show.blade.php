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
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> {{ session('success') }}
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
                    <svg width="80" height="80" fill="none" stroke="#e2e8f0" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
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
                    <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> {{ $product->dealer->state }}</p>
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

        {{-- Reviews --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex items-baseline justify-between border-b pb-3">
                <h2 class="font-extrabold text-slate-800 text-lg">Reviews
                    @if($product->rating_count > 0)
                    <span class="text-sm font-semibold text-amber-500 ml-2 inline-flex items-center gap-1"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display:inline"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> {{ number_format($product->rating, 1) }}</span>
                    <span class="text-xs text-slate-400">({{ $product->rating_count }})</span>
                    @endif
                </h2>
            </div>

            {{-- Write a review --}}
            @if($userCanReview)
            <form method="POST" action="{{ route('marketplace.reviews.store', $product) }}" class="space-y-3 bg-slate-50 rounded-xl p-4 border border-slate-100">
                @csrf
                <p class="text-sm font-bold text-slate-700">Write Your Review</p>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-slate-600">Rating:</label>
                    <div class="flex gap-1" id="star-row">
                        @for($i=1;$i<=5;$i++)
                        <label class="cursor-pointer text-2xl leading-none">
                            <input type="radio" name="rating" value="{{ $i }}" class="sr-only" {{ old('rating')==$i?'checked':'' }}>
                            <span class="star" data-val="{{ $i }}" style="display:inline-flex;color:#cbd5e1;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>
                        </label>
                        @endfor
                    </div>
                </div>
                @error('rating')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                <textarea name="review" rows="3" placeholder="Share your experience with this product…" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">{{ old('review') }}</textarea>
                @error('review')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                <button type="submit" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">Post Review</button>
            </form>
            @elseif($userReview)
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                <p class="text-sm font-bold text-emerald-800 flex items-center gap-1">Your Review
                    <span class="ml-1 inline-flex items-center">{!! str_repeat('<svg width="14" height="14" viewBox="0 0 24 24" fill="#f59e0b" style="display:inline"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>', $userReview->rating) . str_repeat('<svg width="14" height="14" viewBox="0 0 24 24" fill="#cbd5e1" style="display:inline"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>', 5 - $userReview->rating) !!}</span>
                </p>
                <p class="text-sm text-slate-600 mt-1">{{ $userReview->review }}</p>
                <form method="POST" action="{{ route('marketplace.reviews.delete', $userReview) }}" class="mt-2" onsubmit="return confirm('Delete your review?')">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-500 hover:text-red-700 font-semibold">Delete My Review</button>
                </form>
            </div>
            @elseif(auth()->check())
            <p class="text-sm text-slate-400 italic">Only verified buyers of this product can leave a review.</p>
            @else
            <p class="text-sm text-slate-400 italic"><a href="{{ route('login') }}" class="text-[#0F6B3E] font-semibold hover:underline">Log in</a> to write a review.</p>
            @endif

            {{-- Reviews list --}}
            @if($reviews->count())
            <div class="space-y-4">
                @foreach($reviews as $review)
                <div class="border-b border-slate-100 pb-4 last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm flex items-center justify-center">
                                {{ strtoupper(substr($review->user->first_name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="text-sm font-semibold text-slate-700">{{ $review->user->first_name ?? 'User' }} {{ substr($review->user->last_name ?? '', 0, 1) }}.</span>
                            <span class="inline-flex items-center">{!! str_repeat('<svg width="14" height="14" viewBox="0 0 24 24" fill="#f59e0b" style="display:inline"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>', $review->rating) . str_repeat('<svg width="14" height="14" viewBox="0 0 24 24" fill="#e2e8f0" style="display:inline"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>', 5 - $review->rating) !!}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                            @if(auth()->check() && (auth()->id() === $review->user_id || in_array(auth()->user()->role, ['admin','ceo'])))
                            <form method="POST" action="{{ route('marketplace.reviews.delete', $review) }}" onsubmit="return confirm('Delete this review?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-400 hover:text-red-600">Delete</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @if($review->review)
                    <p class="text-sm text-slate-600 mt-2 ml-9">{{ $review->review }}</p>
                    @endif
                </div>
                @endforeach
                <div>{{ $reviews->links() }}</div>
            </div>
            @else
            <p class="text-slate-400 text-sm text-center py-4">No reviews yet. Be the first!</p>
            @endif
        </div>

        <script>
        document.querySelectorAll('#star-row .star').forEach(function(star) {
            star.closest('label').addEventListener('mouseover', function() {
                var val = parseInt(star.dataset.val);
                document.querySelectorAll('#star-row .star').forEach(function(s, i) {
                    s.classList.toggle('text-amber-400', i < val);
                    s.classList.toggle('text-slate-300', i >= val);
                });
            });
        });
        document.querySelector('#star-row').addEventListener('mouseleave', function() {
            var checked = document.querySelector('#star-row input:checked');
            var val = checked ? parseInt(checked.value) : 0;
            document.querySelectorAll('#star-row .star').forEach(function(s, i) {
                s.classList.toggle('text-amber-400', i < val);
                s.classList.toggle('text-slate-300', i >= val);
            });
        });
        document.querySelectorAll('#star-row input').forEach(function(input) {
            input.addEventListener('change', function() {
                var val = parseInt(this.value);
                document.querySelectorAll('#star-row .star').forEach(function(s, i) {
                    s.classList.toggle('text-amber-400', i < val);
                    s.classList.toggle('text-slate-300', i >= val);
                });
            });
        });
        </script>

        {{-- Related Products --}}
        @if($related->count())
        <div class="space-y-4">
            <h2 class="font-extrabold text-slate-800 text-lg">More in {{ $product->category }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($related as $r)
                <a href="{{ route('marketplace.show', $r) }}"
                   class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition flex flex-col">
                    <div class="aspect-square bg-slate-50 flex items-center justify-center">
                        @if($r->image_url)
                        <img src="{{ $r->image_url }}" alt="{{ $r->name }}" class="w-full h-full object-cover">
                        @else <svg width="40" height="40" fill="none" stroke="#e2e8f0" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> @endif
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
