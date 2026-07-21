<x-app-layout>
<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('ceo.staff-roles.index') }}" class="text-slate-400 hover:text-emerald-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Create Staff Role</h2>
            <p class="text-sm text-slate-500 mt-0.5">Define a role and assign module permissions</p>
        </div>
    </div>
</x-slot>

<div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('ceo.staff-roles.store') }}" class="space-y-6">
        @csrf

        {{-- Identity --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Role Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400"
                           placeholder="e.g. Finance Officer, Marketplace Manager">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department / Unit</label>
                    <input type="text" name="department" value="{{ old('department') }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400"
                           placeholder="e.g. Finance, Operations, Technology">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role Description</label>
                <textarea name="description" rows="2"
                          class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400"
                          placeholder="Brief summary of what this role does...">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Key Responsibilities</label>
                <textarea name="responsibilities" rows="3"
                          class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400"
                          placeholder="List the primary duties and responsibilities of this role...">{{ old('responsibilities') }}</textarea>
            </div>
        </div>

        {{-- Permission Matrix --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-start justify-between mb-4 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Permission Matrix</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Select which actions this role can perform in each module.</p>
                </div>
                <button type="button" id="selectAllBtn"
                        class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold px-3 py-1.5 rounded-lg transition">
                    Select All
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left py-2.5 pr-4 font-semibold text-slate-600 min-w-[160px]">Module</th>
                            @foreach($abilities as $key => $label)
                            <th class="text-center py-2.5 px-2 font-semibold text-slate-600 whitespace-nowrap">
                                {{ $label }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($modules as $modKey => $modLabel)
                        @php $oldPerms = old("permissions.{$modKey}", []); @endphp
                        <tr class="hover:bg-emerald-50/50 transition" data-module="{{ $modKey }}">
                            <td class="py-3 pr-4 font-medium text-slate-700">{{ $modLabel }}</td>
                            @foreach($abilities as $abilityKey => $abilityLabel)
                            <td class="text-center py-3 px-2">
                                <input type="checkbox"
                                       name="permissions[{{ $modKey }}][]"
                                       value="{{ $abilityKey }}"
                                       @checked(in_array($abilityKey, $oldPerms))
                                       data-ability="{{ $abilityKey }}"
                                       class="perm-check w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-400 cursor-pointer">
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-400 mt-3">Tip: Selecting "Full Access" on a module grants all abilities for that module automatically.</p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition">
                Create Role
            </button>
            <a href="{{ route('ceo.staff-roles.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition px-3 py-2.5">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Full Access toggles all others in the row
    document.querySelectorAll('[data-ability="full_access"]').forEach(fa => {
        fa.addEventListener('change', () => {
            const row = fa.closest('[data-module]');
            row.querySelectorAll('.perm-check').forEach(cb => { cb.checked = fa.checked; });
        });
    });

    // If all abilities checked, auto-check full_access
    document.querySelectorAll('.perm-check:not([data-ability="full_access"])').forEach(cb => {
        cb.addEventListener('change', () => {
            const row      = cb.closest('[data-module]');
            const allBoxes = [...row.querySelectorAll('.perm-check:not([data-ability="full_access"])')];
            const fullAcc  = row.querySelector('[data-ability="full_access"]');
            if (fullAcc) fullAcc.checked = allBoxes.every(b => b.checked);
        });
    });

    // Select All / Clear All toggle
    const btn = document.getElementById('selectAllBtn');
    let allSelected = false;
    btn.addEventListener('click', () => {
        allSelected = !allSelected;
        document.querySelectorAll('.perm-check').forEach(cb => { cb.checked = allSelected; });
        btn.textContent = allSelected ? 'Clear All' : 'Select All';
    });
});
</script>
</x-app-layout>
