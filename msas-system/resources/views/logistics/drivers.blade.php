<x-app-layout>
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Driver Management</h1>
            <p class="text-slate-500 text-sm">Register and manage your drivers</p>
        </div>
        <button onclick="document.getElementById('modal-add-driver').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Driver
        </button>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Drivers Grid --}}
    @if($drivers->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm text-center py-16 text-slate-400">
        <p class="text-5xl mb-3">👨‍✈️</p>
        <p class="font-semibold text-slate-600">No drivers registered yet</p>
        <p class="text-sm mt-1">Add drivers to assign them to delivery jobs.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($drivers as $d)
        @php
            $sc = match($d->status) { 'available'=>'green', 'on_trip'=>'blue', 'off_duty'=>'slate', default=>'slate' };
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center font-extrabold text-indigo-700 text-base">
                    {{ strtoupper(substr($d->first_name, 0, 1) . substr($d->last_name, 0, 1)) }}
                </div>
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $sc }}-100 text-{{ $sc }}-700">{{ ucwords(str_replace('_',' ',$d->status)) }}</span>
            </div>
            <p class="font-extrabold text-slate-800">{{ $d->full_name }}</p>
            <p class="text-xs text-slate-400 mt-0.5">License: {{ $d->license_number ?? 'N/A' }}</p>
            <p class="text-xs text-slate-400">Phone: {{ $d->phone ?? 'N/A' }}</p>
            @if($d->active_deliveries > 0)
            <p class="text-xs text-blue-600 font-semibold mt-2">{{ $d->active_deliveries }} active trip</p>
            @endif
            <div class="flex gap-2 mt-4">
                <button onclick="openEditDriver({{ $d->id }}, '{{ addslashes($d->first_name) }}', '{{ addslashes($d->last_name) }}', '{{ addslashes($d->license_number ?? '') }}', '{{ addslashes($d->phone ?? '') }}', '{{ $d->status }}', '{{ addslashes($d->notes ?? '') }}')"
                        class="flex-1 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Edit</button>
                <form method="POST" action="{{ route('logistics.drivers.delete', $d) }}" class="flex-1"
                      onsubmit="return confirm('Remove {{ addslashes($d->full_name) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-1.5 text-xs font-bold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Remove</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Add Driver Modal --}}
<div id="modal-add-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Add Driver</h3>
            <button onclick="document.getElementById('modal-add-driver').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" action="{{ route('logistics.drivers.store') }}" class="p-6 space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">First Name *</label><input name="first_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Last Name *</label><input name="last_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">License No.</label><input name="license_number" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Phone</label><input name="phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Status *</label>
                    <select name="status" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="available">Available</option><option value="on_trip">On Trip</option><option value="off_duty">Off Duty</option>
                    </select>
                </div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Notes</label><textarea name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></textarea></div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Add Driver</button>
        </form>
    </div>
</div>

{{-- Edit Driver Modal --}}
<div id="modal-edit-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Edit Driver</h3>
            <button onclick="document.getElementById('modal-edit-driver').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" id="form-edit-driver" class="p-6 space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">First Name *</label><input id="ed_fn" name="first_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Last Name *</label><input id="ed_ln" name="last_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">License No.</label><input id="ed_lic" name="license_number" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Phone</label><input id="ed_phone" name="phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Status *</label>
                    <select id="ed_status" name="status" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="available">Available</option><option value="on_trip">On Trip</option><option value="off_duty">Off Duty</option>
                    </select>
                </div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Notes</label><textarea id="ed_notes" name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></textarea></div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Save Changes</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditDriver(id, fn, ln, lic, phone, status, notes) {
    document.getElementById('form-edit-driver').action = "{{ url('/logistics/drivers') }}/" + id;
    document.getElementById('ed_fn').value     = fn;
    document.getElementById('ed_ln').value     = ln;
    document.getElementById('ed_lic').value    = lic;
    document.getElementById('ed_phone').value  = phone;
    document.getElementById('ed_status').value = status;
    document.getElementById('ed_notes').value  = notes;
    document.getElementById('modal-edit-driver').classList.remove('hidden');
}
</script>
@endpush
</x-app-layout>
