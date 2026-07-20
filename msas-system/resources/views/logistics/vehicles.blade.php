<x-app-layout>
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Fleet Management</h1>
            <p class="text-slate-500 text-sm">Register and manage your vehicles</p>
        </div>
        <button onclick="document.getElementById('modal-add-vehicle').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Vehicle
        </button>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Vehicle Cards --}}
    @if($vehicles->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm text-center py-16 text-slate-400">
        <p class="text-5xl mb-3">🚛</p>
        <p class="font-semibold text-slate-600">No vehicles registered yet</p>
        <p class="text-sm mt-1">Add your first vehicle to start managing deliveries.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($vehicles as $v)
        @php
            $statusColor = match($v->status) {
                'active'       => 'green',
                'maintenance'  => 'amber',
                'retired'      => 'slate',
                default        => 'slate',
            };
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="text-3xl">{{ $v->type_icon }}</div>
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">{{ ucfirst($v->status) }}</span>
            </div>
            <p class="font-extrabold text-slate-800 text-base">{{ $v->reg_number }}</p>
            <p class="text-sm text-slate-500 mt-0.5">{{ $v->make }} {{ $v->model }} {{ $v->year }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ ucfirst($v->vehicle_type) }}{{ $v->capacity_kg ? ' · ' . number_format($v->capacity_kg) . 'kg' : '' }}</p>
            @if($v->active_deliveries > 0)
            <p class="text-xs text-blue-600 font-semibold mt-2">{{ $v->active_deliveries }} active delivery</p>
            @endif
            <div class="flex gap-2 mt-4">
                <button onclick="openEditVehicle({{ $v->id }}, '{{ addslashes($v->reg_number) }}', '{{ addslashes($v->make) }}', '{{ addslashes($v->model ?? '') }}', '{{ $v->year }}', '{{ $v->vehicle_type }}', '{{ $v->capacity_kg }}', '{{ $v->status }}', '{{ addslashes($v->notes ?? '') }}')"
                        class="flex-1 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Edit</button>
                <form method="POST" action="{{ route('logistics.vehicles.delete', $v) }}" class="flex-1"
                      onsubmit="return confirm('Remove {{ addslashes($v->reg_number) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-1.5 text-xs font-bold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Remove</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Add Vehicle Modal --}}
<div id="modal-add-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Register Vehicle</h3>
            <button onclick="document.getElementById('modal-add-vehicle').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" action="{{ route('logistics.vehicles.store') }}" class="p-6 space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Reg Number *</label><input name="reg_number" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="e.g. ABC-123-XY"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Make *</label><input name="make" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="Toyota"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Model</label><input name="model" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="Hilux"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Year</label><input name="year" type="number" min="1990" max="{{ date('Y')+1 }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="{{ date('Y') }}"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Type *</label>
                    <select name="vehicle_type" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="truck">Truck</option><option value="van">Van</option><option value="motorcycle">Motorcycle</option><option value="pickup">Pickup</option><option value="refrigerated">Refrigerated</option>
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Capacity (kg)</label><input name="capacity_kg" type="number" min="0" step="0.01" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="1000"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Status *</label>
                    <select name="status" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="active">Active</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option>
                    </select>
                </div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Notes</label><textarea name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="Any notes about this vehicle…"></textarea></div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Register Vehicle</button>
        </form>
    </div>
</div>

{{-- Edit Vehicle Modal --}}
<div id="modal-edit-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Edit Vehicle</h3>
            <button onclick="document.getElementById('modal-edit-vehicle').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" id="form-edit-vehicle" class="p-6 space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Reg Number *</label><input id="ev_reg" name="reg_number" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Make *</label><input id="ev_make" name="make" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Model</label><input id="ev_model" name="model" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Year</label><input id="ev_year" name="year" type="number" min="1990" max="{{ date('Y')+1 }}" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Type *</label>
                    <select id="ev_type" name="vehicle_type" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="truck">Truck</option><option value="van">Van</option><option value="motorcycle">Motorcycle</option><option value="pickup">Pickup</option><option value="refrigerated">Refrigerated</option>
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Capacity (kg)</label><input id="ev_cap" name="capacity_kg" type="number" min="0" step="0.01" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Status *</label>
                    <select id="ev_status" name="status" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="active">Active</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option>
                    </select>
                </div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Notes</label><textarea id="ev_notes" name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></textarea></div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Save Changes</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditVehicle(id, reg, make, model, year, type, cap, status, notes) {
    const base = "{{ url('/logistics/vehicles') }}/";
    document.getElementById('form-edit-vehicle').action = base + id;
    document.getElementById('ev_reg').value    = reg;
    document.getElementById('ev_make').value   = make;
    document.getElementById('ev_model').value  = model;
    document.getElementById('ev_year').value   = year;
    document.getElementById('ev_type').value   = type;
    document.getElementById('ev_cap').value    = cap;
    document.getElementById('ev_status').value = status;
    document.getElementById('ev_notes').value  = notes;
    document.getElementById('modal-edit-vehicle').classList.remove('hidden');
}
</script>
@endpush
</x-app-layout>
