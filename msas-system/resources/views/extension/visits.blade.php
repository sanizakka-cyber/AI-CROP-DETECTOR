<x-app-layout>
    <x-slot name="header">Farm Visits</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Extension Officer</p>
                <h1 class="text-2xl font-extrabold">Farm Visits</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('extension.farmers') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Farmers</a>
                <a href="{{ route('extension.advisory') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Advisory</a>
            </div>
        </div>

        @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>@endif
        @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">&#9888; {{ session('error') }}</div>@endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Record Visit</h3>
                <form method="POST" action="{{ route('extension.visits.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Farmer</label>
                        <select name="farmer_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">Select farmer...</option>
                            @foreach($farmers as $f)
                            <option value="{{ $f->id }}">{{ $f->name ?: $f->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Visit Date</label>
                        <input type="date" name="visit_date" required value="{{ date('Y-m-d') }}"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Purpose</label>
                        <input type="text" name="purpose" required placeholder="e.g. Crop assessment"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Outcome</label>
                        <input type="text" name="outcome" placeholder="e.g. Recommended treatment"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Save Visit</button>
                </form>
            </div>
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">Visit History</div>
                @forelse($records as $r)
                <div class="px-5 py-4 border-b border-slate-50 hover:bg-slate-50 last:border-0">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-semibold text-slate-800 text-sm">{{ $r->purpose }}</div>
                            @if($r->outcome)<div class="text-xs text-emerald-700 font-medium mt-0.5">Outcome: {{ $r->outcome }}</div>@endif
                            @if($r->notes)<div class="text-xs text-slate-500 mt-1">{{ $r->notes }}</div>@endif
                        </div>
                        <div class="text-xs text-slate-400 flex-shrink-0">{{ \Carbon\Carbon::parse($r->visit_date)->format('d M Y') }}</div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-12 text-center text-slate-400 text-sm">No farm visits recorded yet.</div>
                @endforelse
                <div class="px-5 py-4 border-t border-slate-100">{{ $records->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
