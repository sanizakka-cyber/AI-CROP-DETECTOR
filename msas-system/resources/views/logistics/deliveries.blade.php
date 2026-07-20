<x-app-layout>
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Delivery Management</h1>
            <p class="text-slate-500 text-sm">Track and manage all delivery requests</p>
        </div>
        <button onclick="document.getElementById('modal-add-delivery').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Delivery
        </button>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Status Filter Tabs --}}
    <div class="flex gap-2 flex-wrap">
        @php
            $tabs = ['all'=>'All', 'pending'=>'Pending', 'assigned'=>'Assigned', 'in_transit'=>'In Transit', 'delivered'=>'Delivered', 'failed'=>'Failed'];
        @endphp
        @foreach($tabs as $key => $label)
        <a href="{{ route('logistics.deliveries', ['status' => $key]) }}"
           class="px-4 py-1.5 rounded-full text-xs font-bold transition
                  {{ $status === $key ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            {{ $label }}
            @if(isset($counts[$key]))<span class="ml-1 opacity-70">({{ $counts[$key] }})</span>@endif
        </a>
        @endforeach
    </div>

    {{-- Deliveries Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($deliveries->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <p class="text-5xl mb-3">📦</p>
            <p class="font-semibold text-slate-600">No deliveries found</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ref</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Destination</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Vehicle</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Driver</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Fee</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($deliveries as $d)
                @php
                    $sc = match($d->status) {
                        'delivered' => 'green', 'in_transit','picked_up' => 'blue',
                        'assigned' => 'indigo', 'failed' => 'red', default => 'amber'
                    };
                @endphp
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $d->ref_number }}</td>
                    <td class="px-4 py-3.5 max-w-[200px]">
                        <p class="text-slate-700 truncate text-xs font-medium">{{ $d->delivery_address }}</p>
                        @if($d->contact_name)<p class="text-slate-400 text-xs">{{ $d->contact_name }} {{ $d->contact_phone }}</p>@endif
                    </td>
                    <td class="px-4 py-3.5 text-xs text-slate-500">{{ $d->vehicle?->reg_number ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-xs text-slate-500">{{ $d->driver?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3.5">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $sc }}-100 text-{{ $sc }}-700">
                            {{ ucwords(str_replace('_', ' ', $d->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-right font-semibold text-slate-700">₦{{ number_format($d->delivery_fee) }}</td>
                    <td class="px-5 py-3.5 text-right">
                        @if(!in_array($d->status, ['delivered','failed']))
                        <button onclick="openUpdateStatus({{ $d->id }}, '{{ $d->status }}')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                            Update
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($deliveries->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">{{ $deliveries->appends(['status' => $status])->links() }}</div>
        @endif
        @endif
    </div>
</div>

{{-- Add Delivery Modal --}}
<div id="modal-add-delivery" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">New Delivery Request</h3>
            <button onclick="document.getElementById('modal-add-delivery').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" action="{{ route('logistics.deliveries.store') }}" class="p-6 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Delivery Address *</label>
                <textarea name="delivery_address" required rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="Full delivery address…"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Pickup Address</label>
                <textarea name="pickup_address" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="Pickup location…"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Contact Name</label><input name="contact_name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Contact Phone</label><input name="contact_phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Assign Vehicle</label>
                    <select name="vehicle_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="">— unassigned —</option>
                        @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->reg_number }} ({{ $v->vehicle_type }})</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Assign Driver</label>
                    <select name="driver_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                        <option value="">— unassigned —</option>
                        @foreach($drivers as $dr)<option value="{{ $dr->id }}">{{ $dr->full_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Weight (kg)</label><input name="cargo_weight_kg" type="number" min="0" step="0.01" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Delivery Fee (₦)</label><input name="delivery_fee" type="number" min="0" step="0.01" value="0" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></div>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Cargo Description</label><textarea name="cargo_description" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" placeholder="What's being delivered?"></textarea></div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Create Delivery</button>
        </form>
    </div>
</div>

{{-- Update Status Modal --}}
<div id="modal-update-status" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">Update Delivery Status</h3>
            <button onclick="document.getElementById('modal-update-status').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="POST" id="form-update-status" class="p-6 space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">New Status *</label>
                <select id="us_status" name="status" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="picked_up">Picked Up</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Assign Vehicle</label>
                <select name="vehicle_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                    <option value="">— no change —</option>
                    @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->reg_number }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Assign Driver</label>
                <select name="driver_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                    <option value="">— no change —</option>
                    @foreach($drivers as $dr)<option value="{{ $dr->id }}">{{ $dr->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Notes</label><textarea name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none"></textarea></div>
            <button type="submit" class="w-full py-2.5 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition">Update Status</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openUpdateStatus(id, currentStatus) {
    document.getElementById('form-update-status').action = "{{ url('/logistics/deliveries') }}/" + id + "/status";
    document.getElementById('us_status').value = currentStatus;
    document.getElementById('modal-update-status').classList.remove('hidden');
}
</script>
@endpush
</x-app-layout>
