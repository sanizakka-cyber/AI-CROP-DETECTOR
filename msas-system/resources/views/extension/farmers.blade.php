<x-app-layout>
    <x-slot name="header">Farmer Directory</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Extension Officer</p>
                <h1 class="text-2xl font-extrabold">Farmer Directory</h1>
                <p class="text-emerald-100 text-sm mt-1">{{ $totalCount }} registered farmers.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('extension.visits') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Farm Visits</a>
                <a href="{{ route('extension.advisory') }}" class="px-4 py-2 bg-[#F4A300] hover:bg-[#d4900a] text-white rounded-xl text-sm font-semibold transition">&#9654; Advisory</a>
            </div>
        </div>

        <form method="GET" action="{{ route('extension.farmers') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                class="flex-1 min-w-[160px] border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
            <select name="state" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                <option value="">All States</option>
                @foreach($states as $st)
                <option value="{{ $st }}" {{ request('state')===$st?'selected':'' }}>{{ $st }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">Filter</button>
            <a href="{{ route('extension.farmers') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold">Reset</a>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50"><tr class="text-left text-xs font-bold text-slate-500 uppercase">
                        <th class="px-5 py-3">Farmer</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">State</th><th class="px-5 py-3">LGA</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Joined</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($farmers as $f)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $f->avatarUrl }}" class="w-8 h-8 rounded-full object-cover" alt="">
                                    <div>
                                        <div class="font-semibold text-slate-800 text-xs">{{ $f->name ?: $f->email }}</div>
                                        <div class="text-slate-400 text-xs">ID #{{ str_pad($f->id,4,'0',STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $f->phone ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $f->state ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $f->lga ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $f->is_active?'bg-emerald-100 text-emerald-800':'bg-red-100 text-red-700' }}">
                                    {{ $f->is_active?'Active':'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $f->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400 text-sm">No farmers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-100">{{ $farmers->links() }}</div>
        </div>
    </div>
</x-app-layout>
