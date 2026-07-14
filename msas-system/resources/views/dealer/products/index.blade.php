<x-app-layout>
    <x-slot name="header">Product Catalog</x-slot>

    <div class="space-y-6">

        {{-- Header + Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">My Products</h2>
                <p class="text-slate-500 text-sm mt-0.5">Manage your marketplace listings and inventory</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dealer.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    📦 Orders
                </a>
                <a href="{{ route('dealer.products.create') }}" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition shadow-sm">
                    + Add Product
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium">
            ✓ {{ session('success') }}
        </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Products</p>
                <p class="text-3xl font-extrabold text-[#0F6B3E] mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-emerald-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Active</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-amber-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Low Stock</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-1">{{ $stats['low_stock'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-red-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Out of Stock</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1">{{ $stats['out_stock'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Search</label>
                <input name="search" value="{{ request('search') }}" placeholder="Name, SKU, category…" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Category</label>
                <select name="category" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Stock</label>
                <select name="stock" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All</option>
                    <option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
            <button class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Filter</button>
            @if(request()->hasAny(['search','category','stock']))
            <a href="{{ route('dealer.products.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Clear</a>
            @endif
        </form>

        {{-- Products Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase border-b border-slate-100">
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($products as $product)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $product->brand }} · {{ $product->sku }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-slate-600 text-xs font-medium">{{ $product->category }}</span>
                                @if($product->subcategory)
                                <p class="text-xs text-slate-400">{{ $product->subcategory }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-slate-800">₦{{ number_format($product->selling_price) }}</p>
                                <p class="text-xs text-slate-400">per {{ $product->unit }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $qty   = $product->quantity_in_stock;
                                    $low   = $product->low_stock_threshold ?? 10;
                                    $color = $qty === 0 ? 'text-red-600 bg-red-50' : ($qty <= $low ? 'text-amber-600 bg-amber-50' : 'text-emerald-600 bg-emerald-50');
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold {{ $color }}">
                                    {{ $qty }} units
                                </span>
                                {{-- Quick stock adjust --}}
                                <form method="POST" action="{{ route('dealer.products.stock', $product) }}" class="inline-flex items-center gap-1 ml-1">
                                    @csrf
                                    <input type="number" name="adjustment" placeholder="±" class="w-14 text-xs border border-slate-200 rounded px-1 py-0.5 text-center" step="1">
                                    <button class="text-xs text-[#0F6B3E] font-bold hover:underline" title="Adjust stock">Apply</button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                @if($product->is_active)
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-bold">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('dealer.products.edit', $product) }}" class="text-blue-600 hover:underline text-xs font-semibold">Edit</a>
                                    <form method="POST" action="{{ route('dealer.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <p class="text-slate-400 text-sm mb-2">No products yet.</p>
                                <a href="{{ route('dealer.products.create') }}" class="text-[#0F6B3E] text-sm font-semibold hover:underline">+ Add your first product</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $products->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
