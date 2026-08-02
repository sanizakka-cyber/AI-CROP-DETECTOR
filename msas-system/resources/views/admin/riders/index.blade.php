<x-app-layout>
    <x-slot name="header">Rider Management</x-slot>

    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([
                ['label'=>'Total Riders',  'val'=>$stats['total'],     'color'=>'#64748b'],
                ['label'=>'Available',     'val'=>$stats['available'], 'color'=>'#10b981'],
                ['label'=>'Busy',          'val'=>$stats['busy'],      'color'=>'#f59e0b'],
                ['label'=>'Offline',       'val'=>$stats['offline'],   'color'=>'#94a3b8'],
            ] as $s)
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100 border-l-4" style="border-left-color:{{ $s['color'] }}">
                <div class="text-2xl font-extrabold" style="color:{{ $s['color'] }}">{{ $s['val'] }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase mt-0.5">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Flash --}}
        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        {{-- Header + Add --}}
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800">All Riders</h2>
            <a href="{{ route('admin.riders.create') }}" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-bold hover:bg-[#047857]">
                + Add Rider
            </a>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Rider</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Vehicle</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Deliveries</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Rating</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Account</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($riders as $rider)
                        @php
                            $sc = ['available'=>'bg-green-100 text-green-700','busy'=>'bg-amber-100 text-amber-700','offline'=>'bg-slate-100 text-slate-500'];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 text-sm">{{ $rider->first_name }} {{ $rider->last_name }}</div>
                                <div class="text-xs text-slate-400">{{ $rider->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $rider->phone }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $rider->vehicle_type ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$rider->rider_status ?? 'offline'] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ ucfirst($rider->rider_status ?? 'offline') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ $rider->rider_deliveries ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                @if($rider->rider_rating){{ number_format($rider->rider_rating, 1) }}&nbsp;<svg width="11" height="11" fill="#f59e0b" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>@else&mdash;@endif
                            </td>
                            <td class="px-4 py-3">
                                @if($rider->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">Active</span>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Suspended</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 flex gap-2">
                                <a href="{{ route('admin.riders.show', $rider) }}" class="px-3 py-1 bg-slate-100 text-slate-700 text-xs rounded-lg font-bold hover:bg-slate-200">View</a>
                                <form method="POST" action="{{ route('admin.riders.toggle', $rider) }}" onsubmit="return confirm('Toggle rider account status?')">
                                    @csrf @method('PATCH')
                                    <button class="px-3 py-1 {{ $rider->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-xs rounded-lg font-bold">
                                        {{ $rider->is_active ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">No riders found. <a href="{{ route('admin.riders.create') }}" class="text-[#0F6B3E] font-semibold">Add one now.</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $riders->links() }}
    </div>
</x-app-layout>
