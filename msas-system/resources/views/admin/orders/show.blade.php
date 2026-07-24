<x-app-layout>
    <x-slot name="header">Order {{ $order->order_number }}</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Flash --}}
        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">← Back to Orders</a>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-800">{{ $order->order_number }}</h2>
                    <p class="text-slate-500 text-sm mt-0.5">Placed {{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @php
                        $sc = ['pending'=>'bg-amber-100 text-amber-800','confirmed'=>'bg-blue-100 text-blue-800','processing'=>'bg-purple-100 text-purple-800','shipped'=>'bg-indigo-100 text-indigo-800','delivered'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800','returned'=>'bg-orange-100 text-orange-800'];
                        $badge = $order->riderStatusBadge();
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sc[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                    @if($order->payment_status === 'paid')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">✓ Paid</span>
                    @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Unpaid</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">

            {{-- Parties --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Parties</h3>
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase mb-1">Buyer</div>
                    <div class="font-semibold text-slate-800">{{ $order->buyer?->first_name }} {{ $order->buyer?->last_name }}</div>
                    <div class="text-sm text-slate-500">{{ $order->buyer?->phone }} · {{ $order->buyer?->email }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase mb-1">Seller</div>
                    <div class="font-semibold text-slate-800">{{ $order->dealer?->first_name }} {{ $order->dealer?->last_name }}</div>
                    <div class="text-sm text-slate-500">{{ $order->dealer?->phone }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase mb-1">Delivery Address</div>
                    <div class="text-sm text-slate-600">{{ $order->delivery_address ?? '—' }}</div>
                </div>
            </div>

            {{-- Financials --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-2">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Financials</h3>
                @foreach($order->items as $item)
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">{{ $item->product_name }} × {{ $item->quantity }}</span>
                    <span class="font-semibold text-slate-700">₦{{ number_format($item->total, 2) }}</span>
                </div>
                @endforeach
                <div class="border-t border-slate-100 pt-2 mt-2 space-y-1">
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Subtotal</span><span>₦{{ number_format($order->subtotal, 2) }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Tax (7.5%)</span><span>₦{{ number_format($order->tax, 2) }}</span></div>
                    <div class="flex justify-between text-base font-bold"><span class="text-slate-800">Total</span><span class="text-[#0F6B3E]">₦{{ number_format($order->total, 2) }}</span></div>
                </div>
            </div>
        </div>

        {{-- Rider Assignment --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-4">Rider Assignment</h3>

            @if($order->rider)
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4 p-4 bg-slate-50 rounded-xl">
                <div>
                    <div class="font-semibold text-slate-800">{{ $order->rider->first_name }} {{ $order->rider->last_name }}</div>
                    <div class="text-sm text-slate-500">{{ $order->rider->phone }} · {{ $order->rider->vehicle_type ?? 'Vehicle N/A' }}</div>
                    <div class="text-xs text-slate-400 mt-1">
                        Assigned {{ $order->rider_assigned_at?->format('M d, H:i') }}
                        @if($order->assignedBy) by {{ $order->assignedBy->first_name }} {{ $order->assignedBy->last_name }} @endif
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            </div>
            @endif

            @if(!in_array($order->rider_status ?? '', ['in_transit','completed']) && !in_array($order->status, ['delivered','cancelled','returned']))
            <form method="POST" action="{{ $order->rider_id ? route('admin.orders.reassign', $order) : route('admin.orders.assign', $order) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">{{ $order->rider_id ? 'Reassign to Rider' : 'Assign Rider' }}</label>
                    <select name="rider_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select a rider…</option>
                        @foreach($riders as $rider)
                        <option value="{{ $rider->id }}" {{ $order->rider_id === $rider->id ? 'disabled' : '' }}>
                            {{ $rider->first_name }} {{ $rider->last_name }} — {{ ucfirst($rider->rider_status ?? 'offline') }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if($order->rider_id)
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Reason for Reassignment</label>
                    <input type="text" name="reason" placeholder="Optional reason…" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                @endif
                <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-bold hover:bg-[#047857]">
                    {{ $order->rider_id ? 'Reassign' : 'Assign Rider' }}
                </button>
            </form>
            @endif
        </div>

        {{-- Update Status --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-4">Update Order Status</h3>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex flex-wrap gap-3 items-end">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">New Status</label>
                    <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        @foreach(['pending','confirmed','processing','shipped','delivered','cancelled','returned'] as $s)
                        <option value="{{ $s }}" {{ $order->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Admin Note</label>
                    <input type="text" name="admin_notes" value="{{ $order->admin_notes }}" placeholder="Optional note…" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700">Update</button>
            </form>
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-4">Order Timeline</h3>
            <div class="space-y-2">
                @foreach([
                    ['label'=>'Order Placed',    'ts'=>$order->created_at,        'done'=>true],
                    ['label'=>'Seller Confirmed','ts'=>$order->confirmed_at,      'done'=>!!$order->confirmed_at],
                    ['label'=>'Rider Assigned',  'ts'=>$order->rider_assigned_at, 'done'=>!!$order->rider_assigned_at],
                    ['label'=>'Rider Accepted',  'ts'=>$order->rider_accepted_at, 'done'=>!!$order->rider_accepted_at],
                    ['label'=>'In Transit',      'ts'=>$order->in_transit_at,     'done'=>!!$order->in_transit_at],
                    ['label'=>'Delivered',       'ts'=>$order->delivered_at,      'done'=>!!$order->delivered_at],
                    ['label'=>'Completed',       'ts'=>$order->completed_at,      'done'=>!!$order->completed_at],
                ] as $step)
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full flex-shrink-0 {{ $step['done'] ? 'bg-[#0F6B3E]' : 'bg-slate-200' }}"></div>
                    <div class="flex-1 flex justify-between items-center">
                        <span class="text-sm {{ $step['done'] ? 'font-semibold text-slate-800' : 'text-slate-400' }}">{{ $step['label'] }}</span>
                        @if($step['ts'])
                        <span class="text-xs text-slate-400">{{ $step['ts']->format('M d, H:i') }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
