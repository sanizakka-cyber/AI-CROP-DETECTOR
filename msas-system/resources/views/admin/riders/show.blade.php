<x-app-layout>
    <x-slot name="header">Rider: {{ $rider->first_name }} {{ $rider->last_name }}</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        <a href="{{ route('admin.riders.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">← Back to Riders</a>

        {{-- Profile card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-800">{{ $rider->first_name }} {{ $rider->last_name }}</h2>
                    <div class="text-sm text-slate-500 mt-1">{{ $rider->email }} · {{ $rider->phone }}</div>
                    <div class="text-sm text-slate-500">{{ $rider->vehicle_type ?? 'Vehicle not specified' }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @php
                        $rs = $rider->rider_status ?? 'offline';
                        $rc = ['available'=>'bg-green-100 text-green-700','busy'=>'bg-amber-100 text-amber-700','offline'=>'bg-slate-100 text-slate-500'];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $rc[$rs] ?? 'bg-slate-100 text-slate-500' }}">{{ ucfirst($rs) }}</span>
                    @if($rider->is_active)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                    @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Suspended</span>
                    @endif
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
                @foreach([
                    ['label'=>'Total Deliveries', 'val'=>$rider->rider_deliveries ?? 0],
                    ['label'=>'Success Rate',      'val'=>($successRate ?? 0).'%'],
                    ['label'=>'Rating',            'val'=>$rider->rider_rating ? number_format($rider->rider_rating,1).' ★' : 'N/A'],
                    ['label'=>'Active Orders',     'val'=>$activeOrders],
                ] as $s)
                <div class="bg-slate-50 rounded-xl p-3 text-center">
                    <div class="text-xl font-extrabold text-slate-800">{{ $s['val'] }}</div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">{{ $s['label'] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Controls --}}
            <div class="flex flex-wrap gap-3 mt-5">
                <form method="POST" action="{{ route('admin.riders.toggle', $rider) }}" onsubmit="return confirm('Change rider account status?')">
                    @csrf @method('PATCH')
                    <button class="px-4 py-2 {{ $rider->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-sm rounded-lg font-bold">
                        {{ $rider->is_active ? 'Suspend Account' : 'Activate Account' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.riders.status', $rider) }}" class="flex gap-2 items-center">
                    @csrf @method('PATCH')
                    <select name="rider_status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        @foreach(['available','busy','offline'] as $s)
                        <option value="{{ $s }}" {{ $rs===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-slate-700 text-white text-sm rounded-lg font-bold hover:bg-slate-800">Set Status</button>
                </form>
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-700">Recent Deliveries</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Buyer</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentOrders as $order)
                        @php
                            $sc = ['pending'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-purple-100 text-purple-700','shipped'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700','returned'=>'bg-orange-100 text-orange-700'];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</td>
                            <td class="px-4 py-3 font-semibold text-xs text-slate-700">₦{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($order->rider_status ?? $order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $order->rider_assigned_at?->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 text-sm">No deliveries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
