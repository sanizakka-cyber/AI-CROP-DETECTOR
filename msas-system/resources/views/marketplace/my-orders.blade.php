<x-app-layout>
    <x-slot name="header">My Orders</x-slot>

    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800">My Orders</h2>
                <p class="text-slate-500 text-sm mt-0.5">Track your marketplace purchases</p>
            </div>
            <a href="{{ route('marketplace') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                ← Marketplace
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
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-emerald-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Delivered</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $stats['delivered'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Orders</p>
                <p class="text-3xl font-extrabold text-[#0F6B3E] mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 border-l-4 border-l-blue-400">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Spent</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">₦{{ number_format($stats['spent']) }}</p>
            </div>
        </div>

        {{-- Status Filter --}}
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Status</label>
                <select name="status" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    <option value="">All Orders</option>
                    @foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Filter</button>
            @if(request('status'))<a href="{{ route('marketplace.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">Clear</a>@endif
        </form>

        {{-- Orders --}}
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
            <a href="{{ route('marketplace.orders.show', $order) }}"
               class="block bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md hover:border-slate-200 transition">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div>
                        <span class="font-bold text-slate-800">{{ $order->order_number }}</span>
                        <span class="text-slate-400 text-xs ml-2">{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sc }}">{{ ucfirst($order->status) }}</span>
                        @if($order->payment_status === 'paid')
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">✓ Paid</span>
                        @endif
                    </div>
                </div>
                <div class="text-sm text-slate-500 mb-2">
                    Seller: <span class="font-medium text-slate-700">{{ $order->dealer?->first_name }} {{ $order->dealer?->last_name }}</span>
                    · {{ $order->items->count() }} item(s)
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-400">{{ Str::limit($order->delivery_address, 60) }}</p>
                    <span class="font-extrabold text-[#0F6B3E]">₦{{ number_format($order->total) }}</span>
                </div>
            </a>
            @empty
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 py-16 text-center">
                <div class="text-5xl mb-3">🛒</div>
                <p class="font-semibold text-slate-600">No orders yet</p>
                <p class="text-sm text-slate-400 mt-1 mb-5">Start shopping on the marketplace.</p>
                <a href="{{ route('marketplace') }}" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition">
                    Browse Products
                </a>
            </div>
            @endforelse
        </div>

        @if($orders->hasPages())
        <div>{{ $orders->links() }}</div>
        @endif

    </div>
</x-app-layout>
