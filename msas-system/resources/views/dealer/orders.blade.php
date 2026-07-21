<x-app-layout>
    <x-slot name="header">My Orders</x-slot>

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">Orders</h2>
                <p class="text-slate-500 text-sm mt-0.5">Customer orders placed through the marketplace</p>
            </div>
            <a href="{{ route('dealer.products.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition self-start">
                ← Back to Products
            </a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium">✓ {{ session('success') }}</div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-amber-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-1">{{ $stats['pending'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-blue-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Confirmed</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ $stats['confirmed'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Orders</p>
                <p class="text-3xl font-extrabold text-[#0F6B3E] mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-emerald-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Revenue (Paid)</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">₦{{ number_format($stats['revenue']) }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Status</label>
                <select name="status" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All Statuses</option>
                    @foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Filter</button>
            @if(request('status'))<a href="{{ route('dealer.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Clear</a>@endif
        </form>

        {{-- Orders List --}}
        <div class="space-y-4">
            @forelse($orders as $order)
            @php
                $statusColors = [
                    'pending'    => 'bg-amber-100 text-amber-700',
                    'confirmed'  => 'bg-blue-100 text-blue-700',
                    'processing' => 'bg-purple-100 text-purple-700',
                    'shipped'    => 'bg-indigo-100 text-indigo-700',
                    'delivered'  => 'bg-emerald-100 text-emerald-700',
                    'cancelled'  => 'bg-red-100 text-red-700',
                ];
                $sc = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-700';
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div>
                        <span class="font-bold text-slate-800 text-base">{{ $order->order_number }}</span>
                        <span class="text-slate-400 text-xs ml-3">{{ $order->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sc }}">{{ ucfirst($order->status) }}</span>
                        @if($order->payment_status === 'paid')
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">✓ Paid</span>
                        @else
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs">Unpaid</span>
                        @endif
                    </div>
                </div>

                <div class="text-sm text-slate-600 mb-3">
                    <span class="font-semibold text-slate-700">Buyer:</span> {{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}
                    @if($order->delivery_address)
                    <span class="ml-3"><span class="font-semibold text-slate-700">Delivery:</span> {{ $order->delivery_address }}</span>
                    @endif
                </div>

                <div class="border border-slate-100 rounded-xl overflow-hidden mb-3">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-xs font-bold text-slate-500 uppercase text-left">
                                <th class="px-3 py-2">Product</th>
                                <th class="px-3 py-2">Qty</th>
                                <th class="px-3 py-2 text-right">Unit Price</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($order->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-medium text-slate-700">{{ $item->product_name }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $item->quantity }}</td>
                                <td class="px-3 py-2 text-slate-700 text-right">₦{{ number_format($item->unit_price) }}</td>
                                <td class="px-3 py-2 font-bold text-slate-800 text-right">₦{{ number_format($item->total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm">
                        <span class="text-slate-500">Subtotal:</span> <span class="font-semibold">₦{{ number_format($order->subtotal) }}</span>
                        <span class="text-slate-300 mx-1">·</span>
                        <span class="text-slate-500">VAT:</span> <span class="font-semibold">₦{{ number_format($order->tax) }}</span>
                        <span class="text-slate-300 mx-1">·</span>
                        <span class="font-bold text-[#0F6B3E] text-base">Total: ₦{{ number_format($order->total) }}</span>
                    </div>
                    @if(!in_array($order->status, ['delivered','cancelled']))
                    <form method="POST" action="{{ route('dealer.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <div class="flex items-center gap-2">
                            <select name="status" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                                @foreach(['confirmed','processing','shipped','delivered'] as $s)
                                <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Update</button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 py-16 text-center">
                <p class="text-slate-400 text-base">No orders yet.</p>
                <p class="text-slate-300 text-sm mt-1">Orders from the marketplace will appear here.</p>
            </div>
            @endforelse
        </div>

        @if($orders->hasPages())
        <div>{{ $orders->links() }}</div>
        @endif

    </div>
</x-app-layout>
