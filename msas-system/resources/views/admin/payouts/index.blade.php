<x-app-layout>
    <x-slot name="header">Seller Payout Management</x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            @foreach([
                ['label'=>'Requested', 'val'=>$stats['requested'], 'color'=>'#F4A300', 'bg'=>'#fef9c3'],
                ['label'=>'Approved',  'val'=>$stats['approved'],  'color'=>'#2D9CDB', 'bg'=>'#eff6ff'],
                ['label'=>'Paid Out',  'val'=>$stats['paid'],      'color'=>'#1FA84A', 'bg'=>'#f0fdf4'],
                ['label'=>'Rejected',  'val'=>$stats['rejected'],  'color'=>'#dc2626', 'bg'=>'#fef2f2'],
                ['label'=>'Total Due', 'val'=>'₦'.number_format($stats['total_due'],2), 'color'=>'#7C3AED', 'bg'=>'#f5f3ff'],
            ] as $s)
            <div style="background:{{ $s['bg'] }};border-radius:12px;padding:14px 16px;border-left:4px solid {{ $s['color'] }};">
                <div style="font-size:20px;font-weight:900;color:{{ $s['color'] }};">{{ $s['val'] }}</div>
                <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Flash --}}
        @foreach(['success','error','info'] as $type)
        @if(session($type))
        <div class="px-4 py-3 rounded-xl text-sm font-medium
            {{ $type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : ($type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($type) }}
        </div>
        @endif
        @endforeach

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap gap-3 items-center">
            <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['requested','approved','paid','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Seller</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Order Total</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Payout (95%)</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Requested</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($payouts as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-700">{{ $order->dealer?->first_name }} {{ $order->dealer?->last_name }}</div>
                                <div class="text-xs text-slate-400">{{ $order->dealer?->email }}</div>
                                <div class="text-xs text-slate-400">{{ $order->dealer?->phone }}</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-700">₦{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3 font-bold text-[#0F6B3E]">₦{{ number_format($order->payout_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @php $statusColors = ['requested'=>'bg-yellow-100 text-yellow-800','approved'=>'bg-blue-100 text-blue-800','paid'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800']; @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $statusColors[$order->payout_status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($order->payout_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $order->payout_requested_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if($order->payout_status === 'requested')
                                    <form method="POST" action="{{ route('admin.payouts.approve', $order) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg font-bold hover:bg-blue-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.payouts.reject', $order) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg font-bold hover:bg-red-700"
                                            onclick="return confirm('Reject this payout request?')">Reject</button>
                                    </form>
                                    @elseif($order->payout_status === 'approved')
                                    <form method="POST" action="{{ route('admin.payouts.markPaid', $order) }}" class="flex gap-2 items-center">
                                        @csrf
                                        <input type="text" name="payout_reference" required placeholder="Transfer ref…"
                                            class="border border-slate-200 rounded-lg px-2 py-1 text-xs w-28">
                                        <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg font-bold hover:bg-green-700">Mark Paid</button>
                                    </form>
                                    @elseif($order->payout_status === 'paid')
                                    <span class="text-xs text-green-700 font-semibold">
                                        Paid {{ $order->payout_paid_at?->format('M d') }}<br>
                                        <span class="font-mono text-slate-400">{{ $order->payout_reference }}</span>
                                    </span>
                                    @else
                                    <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </div>
                                @if($order->payout_notes)
                                <div class="text-xs text-slate-400 mt-1">{{ Str::limit($order->payout_notes, 60) }}</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm">No payout requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $payouts->links() }}
    </div>
</x-app-layout>
