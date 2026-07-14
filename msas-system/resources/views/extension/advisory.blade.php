<x-app-layout>
    <x-slot name="header">Advisory Records</x-slot>

    <div class="space-y-6">
        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Extension Officer</p>
                <h1 class="text-2xl font-extrabold">Advisory Records</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('extension.farmers') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&larr; Farmers</a>
                <a href="{{ route('extension.visits') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Visits</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">&#9888; {{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Record Advisory</h3>
                <form method="POST" action="{{ route('extension.advisory.store') }}" class="space-y-4">
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
                        <label class="block text-xs font-bold text-slate-600 mb-1">Category</label>
                        <select name="category" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="Crop">Crop</option><option value="Livestock">Livestock</option>
                            <option value="Soil">Soil</option><option value="Pest">Pest</option>
                            <option value="Disease">Disease</option><option value="General">General</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Subject</label>
                        <input type="text" name="subject" required placeholder="Advisory subject..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Advisory Notes</label>
                        <textarea name="advice" required rows="4"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">Save Advisory</button>
                </form>
            </div>
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">Advisory History</div>
                @forelse($records as $r)
                <div class="px-5 py-4 border-b border-slate-50 hover:bg-slate-50 last:border-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">{{ $r->category ?? 'General' }}</span>
                                <span class="font-semibold text-slate-800 text-sm">{{ $r->subject }}</span>
                            </div>
                            <p class="text-xs text-slate-500 line-clamp-2">{{ $r->advice }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-12 text-center text-slate-400 text-sm">No advisory records yet.</div>
                @endforelse
                <div class="px-5 py-4 border-t border-slate-100">{{ $records->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
