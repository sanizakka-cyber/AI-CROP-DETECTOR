<x-app-layout>
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Logistics Dashboard</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome back, {{ auth()->user()->first_name }} — manage your fleet and deliveries</p>
        </div>
        <a href="{{ route('logistics.deliveries') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Delivery
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'Active Vehicles', 'value'=>$activeVehicles, 'sub'=>"of {$totalVehicles} total", 'icon'=>'🚛', 'color'=>'blue'],
            ['label'=>'Available Drivers', 'value'=>$availableDrivers, 'sub'=>"of {$totalDrivers} total", 'icon'=>'👨‍✈️', 'color'=>'indigo'],
            ['label'=>'Pending Deliveries', 'value'=>$pendingDeliveries, 'sub'=>"{$inTransit} in transit", 'icon'=>'📦', 'color'=>'amber'],
            ['label'=>'Revenue Earned', 'value'=>'₦'.number_format($totalRevenue), 'sub'=>"{$completedToday} completed today", 'icon'=>'💰', 'color'=>'green'],
        ] as $card)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">{{ $card['icon'] }}</span>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $card['label'] }}</span>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $card['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['label'=>'Fleet', 'desc'=>'Manage vehicles', 'icon'=>'🚛', 'route'=>'logistics.vehicles', 'color'=>'blue'],
            ['label'=>'Drivers', 'desc'=>'Manage drivers', 'icon'=>'👨‍✈️', 'route'=>'logistics.drivers', 'color'=>'indigo'],
            ['label'=>'Deliveries', 'desc'=>'All delivery jobs', 'icon'=>'📦', 'route'=>'logistics.deliveries', 'color'=>'green'],
        ] as $action)
        <a href="{{ route($action['route']) }}"
           class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-{{ $action['color'] }}-50 border border-{{ $action['color'] }}-100 flex items-center justify-center text-2xl flex-shrink-0">
                {{ $action['icon'] }}
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ $action['label'] }}</p>
                <p class="text-xs text-slate-400">{{ $action['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Recent Deliveries --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-extrabold text-slate-700">Recent Deliveries</h2>
            <a href="{{ route('logistics.deliveries') }}" class="text-sm text-green-600 font-semibold hover:underline">View All</a>
        </div>
        @if($recentDeliveries->isEmpty())
        <div class="text-center py-12 text-slate-400">
            <p class="text-4xl mb-2">📦</p>
            <p class="font-semibold">No deliveries yet</p>
            <p class="text-sm mt-1">Add your first delivery request to get started.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ref</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Destination</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Driver</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Fee</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($recentDeliveries as $d)
                @php
                    $statusColor = match($d->status) {
                        'delivered'  => 'green',
                        'in_transit','picked_up' => 'blue',
                        'assigned'   => 'indigo',
                        'failed'     => 'red',
                        default      => 'amber',
                    };
                @endphp
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $d->ref_number }}</td>
                    <td class="px-4 py-3.5 text-slate-700 max-w-[180px] truncate">{{ $d->delivery_address }}</td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs">{{ $d->driver?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3.5">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">
                            {{ ucwords(str_replace('_', ' ', $d->status)) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right font-semibold text-slate-700">₦{{ number_format($d->delivery_fee) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
</x-app-layout>
