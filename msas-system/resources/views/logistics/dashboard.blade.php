<x-app-layout>
<div class="max-w-7xl mx-auto space-y-6">

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

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
            ['label'=>'Active Vehicles',   'value'=>$activeVehicles,            'sub'=>"of {$totalVehicles} total",    'color'=>'#0288D1', 'path'=>'<rect x="1" y="3" width="15" height="13" rx="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
            ['label'=>'Available Drivers', 'value'=>$availableDrivers,          'sub'=>"of {$totalDrivers} total",    'color'=>'#7C3AED', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
            ['label'=>'Pending Deliveries','value'=>$pendingDeliveries,          'sub'=>"{$inTransit} in transit",     'color'=>'#d97706', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>'],
            ['label'=>'Revenue Earned',    'value'=>'₦'.number_format($totalRevenue), 'sub'=>"{$completedToday} completed today", 'color'=>'#0F6B3E', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ] as $card)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:{{ $card['color'] }}18">
                    <svg width="18" height="18" fill="none" stroke="{{ $card['color'] }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $card['path'] !!}</svg>
                </div>
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
            ['label'=>'Fleet',      'desc'=>'Manage vehicles',   'color'=>'#0288D1', 'path'=>'<rect x="1" y="3" width="15" height="13" rx="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>', 'route'=>'logistics.vehicles'],
            ['label'=>'Drivers',    'desc'=>'Manage drivers',    'color'=>'#7C3AED', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',                                                                                                                                         'route'=>'logistics.drivers'],
            ['label'=>'Deliveries', 'desc'=>'All delivery jobs', 'color'=>'#0F6B3E', 'path'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',                                                                                                                                          'route'=>'logistics.deliveries'],
        ] as $action)
        <a href="{{ route($action['route']) }}"
           class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:{{ $action['color'] }}1a">
                <svg width="22" height="22" fill="none" stroke="{{ $action['color'] }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $action['path'] !!}</svg>
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
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:#0F6B3E18">
                <svg width="28" height="28" fill="none" stroke="#0F6B3E" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <p class="font-semibold">No deliveries yet</p>
            <p class="text-sm mt-1">Add your first delivery request to get started.</p>
        </div>
        @else
        <div class="overflow-x-auto">
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
        </div>
        @endif
    </div>
</div>
</x-app-layout>
