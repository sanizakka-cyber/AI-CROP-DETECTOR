<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vaccination Records</h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700">
                + Record Vaccination
            </button>
        </div>
    </x-slot>
    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold shadow-sm">{{ session('success') }}</div>
            @endif
            @if($upcoming->isNotEmpty())
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-amber-800 mb-3">Due Within 30 Days ({{ $upcoming->count() }})</h3>
                <div class="space-y-2">
                    @foreach($upcoming as $v)
                    <div class="flex items-center justify-between bg-white rounded-xl px-4 py-3 border border-amber-100 text-sm">
                        <div>
                            <span class="font-semibold text-slate-800">{{ $v->vaccine_name }}</span>
                            <span class="text-slate-500 ml-2">{{ ucfirst($v->animal_type) }}</span>
                            <span class="text-slate-400 ml-2">for {{ $v->farmer?->first_name }} {{ $v->farmer?->last_name }}</span>
                        </div>
                        <span class="text-amber-700 font-bold text-xs">Due {{ $v->next_due_at->format('M d, Y') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="bg-white shadow-sm rounded-2xl border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100"><h3 class="text-sm font-bold text-slate-700">All Records</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-600 text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-5 py-3">Farmer</th><th class="px-4 py-3">Animal Type</th>
                                <th class="px-4 py-3">Vaccine</th><th class="px-4 py-3">Administered</th>
                                <th class="px-4 py-3">Next Due</th><th class="px-4 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($records as $v)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $v->farmer?->first_name }} {{ $v->farmer?->last_name }}</td>
                                <td class="px-4 py-3 capitalize">{{ $v->animal_type }}</td>
                                <td class="px-4 py-3 font-medium">{{ $v->vaccine_name }}</td>
                                <td class="px-4 py-3">{{ $v->administered_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">{{ $v->next_due_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 max-w-xs truncate">{{ $v->notes ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400 text-sm">No records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($records->hasPages())<div class="px-5 py-4 border-t border-slate-100">{{ $records->links() }}</div>@endif
            </div>
        </div>
    </div>
    <div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Record Vaccination</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
            </div>
            <form action="{{ route('vet.vaccinations.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Farmer *</label>
                        <select name="farmer_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                            <option value="">Select farmer...</option>
                            @foreach($farmers as $f)
                                <option value="{{ $f->id }}">{{ $f->first_name }} {{ $f->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Animal Type *</label>
                        <input type="text" name="animal_type" required placeholder="e.g. Cattle, Goat"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Vaccine Name *</label>
                        <input type="text" name="vaccine_name" required placeholder="e.g. PPR, Newcastle"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Date Administered *</label>
                        <input type="date" name="administered_at" required value="{{ now()->format('Y-m-d') }}"
                               max="{{ now()->format('Y-m-d') }}" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Next Due Date</label>
                        <input type="date" name="next_due_at" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Notes / Batch No.</label>
                        <textarea name="notes" rows="2" placeholder="Dosage, herd size, batch number..."
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl text-sm font-bold">Save Record</button>
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-500 text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @if($errors->any())<script>document.getElementById('addModal').classList.remove('hidden')</script>@endif
</x-app-layout>
