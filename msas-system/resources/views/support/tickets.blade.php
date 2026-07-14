<x-app-layout>
    <x-slot name="header">Support Tickets</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-slate-900 to-blue-800 rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Customer Support</p>
                <h1 class="text-2xl font-extrabold">Support Tickets</h1>
                <p class="text-blue-100 text-sm mt-1">Manage and resolve customer support requests.</p>
            </div>
            <a href="{{ route('support.tickets.create') }}" class="px-4 py-2 bg-white text-slate-800 rounded-xl text-sm font-bold hover:bg-blue-50 transition">+ New Ticket</a>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-semibold">&#9888; {{ session('error') }}</div>
        @endif

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['Open', $stats['open'], 'border-l-red-500', 'text-red-600'],
                ['In Progress', $stats['in_progress'], 'border-l-amber-500', 'text-amber-600'],
                ['Resolved', $stats['resolved'], 'border-l-emerald-500', 'text-emerald-600'],
                ['Total', $stats['total'], 'border-l-blue-500', 'text-blue-600'],
            ] as [$lbl, $val, $bdr, $clr])
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 {{ $bdr }}">
                <p class="text-xs font-bold text-slate-500 uppercase">{{ $lbl }}</p>
                <p class="text-3xl font-extrabold {{ $clr }} mt-1">{{ $val }}</p>
            </div>
            @endforeach
        </div>

        {{-- Filter & Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-4 border-b border-slate-100">
                <form method="GET" action="{{ route('support.tickets') }}" class="flex flex-wrap gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tickets..."
                        class="flex-1 min-w-[160px] border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="open"        {{ request('status')==='open'        ?'selected':'' }}>Open</option>
                        <option value="in_progress" {{ request('status')==='in_progress' ?'selected':'' }}>In Progress</option>
                        <option value="resolved"    {{ request('status')==='resolved'    ?'selected':'' }}>Resolved</option>
                        <option value="closed"      {{ request('status')==='closed'      ?'selected':'' }}>Closed</option>
                    </select>
                    <select name="priority" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Priority</option>
                        <option value="urgent" {{ request('priority')==='urgent' ?'selected':'' }}>Urgent</option>
                        <option value="high"   {{ request('priority')==='high'   ?'selected':'' }}>High</option>
                        <option value="medium" {{ request('priority')==='medium' ?'selected':'' }}>Medium</option>
                        <option value="low"    {{ request('priority')==='low'    ?'selected':'' }}>Low</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Filter</button>
                    <a href="{{ route('support.tickets') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Reset</a>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($tickets as $t)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-xs text-slate-400 font-mono">#{{ str_pad($t->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800 text-xs max-w-[200px] truncate">{{ $t->subject }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $t->category ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php $p = $t->priority ?? 'low'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{
                                    $p==='urgent'?'bg-red-100 text-red-800':
                                    ($p==='high'?'bg-orange-100 text-orange-800':
                                    ($p==='medium'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-600')) }}">
                                    {{ ucfirst($p) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php $s = $t->status ?? 'open'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{
                                    $s==='open'?'bg-red-100 text-red-800':
                                    ($s==='in_progress'?'bg-amber-100 text-amber-800':
                                    ($s==='resolved'?'bg-emerald-100 text-emerald-800':'bg-slate-100 text-slate-600')) }}">
                                    {{ str_replace('_',' ', ucfirst($s)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('support.tickets.show', $t->id) }}" class="px-2 py-1 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700">View</a>
                                    @if($s !== 'resolved' && $s !== 'closed')
                                    <form method="POST" action="{{ route('support.tickets.resolve', $t->id) }}">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700">Resolve</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400 text-sm">No tickets found. <a href="{{ route('support.tickets.create') }}" class="text-blue-600 font-semibold">Create one &rarr;</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 border-t border-slate-100">{{ $tickets->links() }}</div>
        </div>
    </div>
</x-app-layout>
