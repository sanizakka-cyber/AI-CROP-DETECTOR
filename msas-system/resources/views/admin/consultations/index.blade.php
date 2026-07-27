<x-app-layout>
    <x-slot name="header">Consultation Management</x-slot>

    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach([
                ['label'=>'Total',      'val'=>$stats['total'],      'color'=>'#64748b'],
                ['label'=>'Open',       'val'=>$stats['open'],       'color'=>'#f59e0b'],
                ['label'=>'Unassigned', 'val'=>$stats['unassigned'], 'color'=>'#ef4444'],
                ['label'=>'Resolved',   'val'=>$stats['resolved'],   'color'=>'#10b981'],
                ['label'=>'Livestock',  'val'=>$stats['livestock'],  'color'=>'#8b5cf6'],
                ['label'=>'Crop',       'val'=>$stats['crop'],       'color'=>'#059669'],
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

        {{-- Filters --}}
        <form method="GET" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <input name="search" value="{{ request('search') }}" placeholder="Search farmer name…"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 min-w-[160px]">
            <select name="case_type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="livestock" {{ request('case_type')==='livestock'?'selected':'' }}>Livestock (Vet)</option>
                <option value="crop" {{ request('case_type')==='crop'?'selected':'' }}>Crop (Agronomist)</option>
            </select>
            <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="open" {{ request('status')==='open'?'selected':'' }}>Open</option>
                <option value="resolved" {{ request('status')==='resolved'?'selected':'' }}>Resolved</option>
                <option value="cancelled" {{ request('status')==='cancelled'?'selected':'' }}>Cancelled</option>
            </select>
            <select name="assigned" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">All Assignment</option>
                <option value="unassigned" {{ request('assigned')==='unassigned'?'selected':'' }}>Unassigned</option>
                <option value="assigned" {{ request('assigned')==='assigned'?'selected':'' }}>Assigned</option>
            </select>
            <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            @if(request()->hasAny(['search','case_type','status','assigned']))
            <a href="{{ route('admin.consultations.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Farmer</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Symptoms</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Assigned Expert</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Quick Assign</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($consultations as $c)
                        @php
                            $sc = ['open'=>'bg-amber-100 text-amber-700','resolved'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
                            $tc = $c->case_type === 'crop' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700';
                            $experts = $c->case_type === 'crop' ? $agronomists : $vets;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 text-xs">{{ $c->farmer?->first_name }} {{ $c->farmer?->last_name }}</div>
                                <div class="text-xs text-slate-400">{{ $c->farmer?->phone }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tc }}">{{ ucfirst($c->case_type) }}</span>
                                    @if($c->animal_type)
                                    <span class="text-[10px] text-slate-400">{{ $c->animal_type }}</span>
                                    @elseif($c->crop_type)
                                    <span class="text-[10px] text-slate-400">{{ $c->crop_type }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-[150px] truncate">{{ $c->symptoms }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$c->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($c->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700">
                                @if($c->expert)
                                <span class="font-semibold">{{ $c->expert->first_name }} {{ $c->expert->last_name }}</span>
                                <div class="text-[10px] text-slate-400">{{ ucfirst($c->expert->role) }}</div>
                                @else
                                <span class="text-red-500 font-semibold text-[10px]">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($c->status === 'open')
                                <form method="POST" action="{{ route('admin.consultations.assign', $c) }}" class="flex gap-1.5">
                                    @csrf
                                    <select name="expert_id" required class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs">
                                        <option value="">{{ $c->case_type === 'crop' ? 'Select agronomist' : 'Select vet' }}…</option>
                                        @foreach($experts as $e)
                                        <option value="{{ $e->id }}" {{ $c->expert_id===$e->id?'selected':'' }}>{{ $e->first_name }} {{ $e->last_name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="px-2 py-1.5 bg-[#0F6B3E] text-white text-xs rounded-lg font-bold hover:bg-[#047857]">Assign</button>
                                </form>
                                @else
                                <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $c->created_at->format('M d, H:i') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.consultations.show', $c) }}" class="px-3 py-1 bg-slate-100 text-slate-700 text-xs rounded-lg font-bold hover:bg-slate-200">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">No consultations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $consultations->links() }}
    </div>
</x-app-layout>
