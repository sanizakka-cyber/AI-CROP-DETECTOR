<x-app-layout>
    <x-slot name="header">{{ $product ? 'Edit Product' : 'Add New Product' }}</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex items-center gap-3">
            <a href="{{ route('dealer.products.index') }}" class="text-slate-400 hover:text-slate-600 transition">← Back to Products</a>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ $product ? route('dealer.products.update', $product) : route('dealer.products.store') }}" class="space-y-6">
            @csrf
            @if($product) @method('PUT') @endif

            {{-- Basic Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h3 class="font-bold text-slate-800 border-b pb-3">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input name="name" value="{{ old('name', $product?->name) }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Category *</label>
                        <select name="category" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $product?->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Subcategory</label>
                        <input name="subcategory" value="{{ old('subcategory', $product?->subcategory) }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Brand</label>
                        <input name="brand" value="{{ old('brand', $product?->brand) }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Manufacturer</label>
                        <input name="manufacturer" value="{{ old('manufacturer', $product?->manufacturer) }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30 resize-none">{{ old('description', $product?->description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit *</label>
                        <input name="unit" value="{{ old('unit', $product?->unit) }}" placeholder="e.g. 500ml bottle, 25kg bag" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">SKU</label>
                        <input name="sku" value="{{ old('sku', $product?->sku) }}" placeholder="Auto-generated if empty" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                </div>
            </div>

            {{-- Pricing & Inventory --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h3 class="font-bold text-slate-800 border-b pb-3">Pricing & Inventory</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Cost Price (₦) *</label>
                        <input name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price', $product?->cost_price) }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Selling Price (₦) *</label>
                        <input name="selling_price" type="number" step="0.01" min="0" value="{{ old('selling_price', $product?->selling_price) }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Quantity in Stock *</label>
                        <input name="quantity_in_stock" type="number" min="0" value="{{ old('quantity_in_stock', $product?->quantity_in_stock ?? 0) }}" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Low Stock Alert Threshold</label>
                        <input name="low_stock_threshold" type="number" min="0" value="{{ old('low_stock_threshold', $product?->low_stock_threshold ?? 10) }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Expiry Date</label>
                        <input name="expiry_date" type="date" value="{{ old('expiry_date', $product?->expiry_date?->format('Y-m-d')) }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 text-[#0F6B3E] rounded">
                            <span class="text-sm font-semibold text-slate-700">Active (visible to buyers)</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Usage & Storage --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h3 class="font-bold text-slate-800 border-b pb-3">Usage & Storage</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Usage Instructions</label>
                        <textarea name="usage_instructions" rows="3" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30 resize-none" placeholder="How to use this product…">{{ old('usage_instructions', $product?->usage_instructions) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Dosage Instructions</label>
                        <textarea name="dosage_instructions" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30 resize-none" placeholder="Recommended dosage and frequency…">{{ old('dosage_instructions', $product?->dosage_instructions) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Storage Requirements</label>
                        <input name="storage_requirements" value="{{ old('storage_requirements', $product?->storage_requirements) }}" placeholder="e.g. Store in cool dry place below 25°C" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tags <span class="font-normal text-slate-400">(comma-separated, used for AI recommendations)</span></label>
                        <input name="tags" value="{{ old('tags', is_array($product?->tags) ? implode(', ', $product->tags) : '') }}" placeholder="ivermectin, dewormer, cattle, poultry" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex gap-3 justify-end">
                <a href="{{ route('dealer.products.index') }}" class="px-5 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition shadow-sm">
                    {{ $product ? 'Update Product' : 'Add Product' }}
                </button>
            </div>
        </form>

    </div>
</x-app-layout>
