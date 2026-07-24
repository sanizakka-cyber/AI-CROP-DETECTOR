<x-app-layout>
    <x-slot name="header">My Deliveries</x-slot>

    <div class="space-y-6">

        <a href="{{ route('rider.dashboard') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">← Dashboard</a>

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3">
            <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                @foreach(['assigned','accepted','in_transit','completed','declined'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            @if(request('status'))
            <a href="{{ route('rider.orders') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Clear</a>
            @endif
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Buyer</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Rider Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Assigned</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($orders as $order)
                        @php
                            $rc = ['assigned'=>'bg-amber-100 text-amber-700','accepted'=>'bg-blue-100 text-blue-700','in_transit'=>'bg-purple-100 text-purple-700','completed'=>'bg-green-100 text-green-700','declined'=>'bg-red-100 text-red-700'];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-700 text-xs">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>
                                <div class="text-xs text-slate-400">{{ $order->buyer?->phone }}</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-xs text-slate-700">₦{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rc[$order->rider_status ?? ''] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst(str_replace('_',' ', $order->rider_status ?? 'unassigned')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $order->rider_assigned_at?->format('M d, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('rider.orders.show', $order) }}" class="px-3 py-1 bg-slate-100 text-slate-700 text-xs rounded-lg font-bold hover:bg-slate-200">Details</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">No deliveries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $orders->links() }}
    </div>
</x-app-layout>
