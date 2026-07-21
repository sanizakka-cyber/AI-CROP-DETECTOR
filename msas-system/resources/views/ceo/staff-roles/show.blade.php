<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('ceo.staff-roles.index') }}" class="text-slate-400 hover:text-emerald-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-800">{{ $staffRole->name }}</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $staffRole->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $staffRole->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($staffRole->department)
                <p class="text-sm text-slate-500 mt-0.5">{{ $staffRole->department }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('ceo.staff-roles.edit', $staffRole) }}"
               class="text-sm bg-white border border-slate-200 hover:border-emerald-400 text-slate-700 font-semibold px-4 py-2 rounded-xl transition">
                Edit Role
            </a>
            <form method="POST" action="{{ route('ceo.staff-roles.toggle', $staffRole) }}">
                @csrf @method('PATCH')
                <button class="text-sm font-semibold px-4 py-2 rounded-xl border transition
                    {{ $staffRole->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                    {{ $staffRole->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>
</x-slot>

<div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Role Info + Permissions --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Description + Responsibilities --}}
            @if($staffRole->description || $staffRole->responsibilities)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                @if($staffRole->description)
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Description</p>
                    <p class="text-sm text-slate-700">{{ $staffRole->description }}</p>
                </div>
                @endif
                @if($staffRole->responsibilities)
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Responsibilities</p>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $staffRole->responsibilities }}</p>
                </div>
                @endif
            </div>
            @endif

            {{-- Permission Matrix (read-only) --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-base font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Permission Matrix</h3>
                @php $perms = $staffRole->permissions ?? []; @endphp
                @if(empty(array_filter($perms)))
                <p class="text-sm text-slate-400 text-center py-6">No permissions assigned to this role.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="text-left py-2 pr-4 font-semibold text-slate-500 min-w-[150px]">Module</th>
                                @foreach($abilities as $key => $label)
                                <th class="text-center py-2 px-1.5 font-semibold text-slate-500 whitespace-nowrap">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($modules as $modKey => $modLabel)
                            @php $modPerms = $perms[$modKey] ?? []; @endphp
                            @if(!empty($modPerms))
                            <tr>
                                <td class="py-2.5 pr-4 font-medium text-slate-700">{{ $modLabel }}</td>
                                @foreach($abilities as $abilityKey => $abilityLabel)
                                @php $has = in_array('full_access', $modPerms) || in_array($abilityKey, $modPerms); @endphp
                                <td class="text-center py-2.5 px-1.5">
                                    @if($has)
                                    <span class="text-emerald-500">&#10003;</span>
                                    @else
                                    <span class="text-slate-200">—</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- Assigned Staff --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-800">Assigned Staff ({{ $assignedUsers->total() }})</h3>
                    <a href="{{ route('ceo.staff.create') }}?role={{ $staffRole->id }}"
                       class="text-xs bg-emerald-600 text-white font-semibold px-3 py-1.5 rounded-lg hover:bg-emerald-700 transition">
                        Add Staff
                    </a>
                </div>
                @if($assignedUsers->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">No staff assigned to this role yet.</p>
                @else
                <div class="space-y-3">
                    @foreach($assignedUsers as $user)
                    <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                        <div class="flex items-center gap-3">
                            <img src="{{ $user->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover">
                            <div>
                                <a href="{{ route('ceo.staff.show', $user) }}" class="text-sm font-semibold text-slate-700 hover:text-emerald-600">{{ $user->name }}</a>
                                <p class="text-xs text-slate-400">{{ $user->email }} · {{ $user->role_label }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs {{ $user->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <form method="POST" action="{{ route('ceo.staff.remove-role', $user) }}">
                                @csrf @method('DELETE')
                                <input type="hidden" name="staff_role_id" value="{{ $staffRole->id }}">
                                <button class="text-xs text-rose-500 hover:underline font-medium">Remove</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $assignedUsers->links() }}</div>
                @endif
            </div>
        </div>

        {{-- Right: Meta + Audit Log --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Role Details</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Slug</dt>
                        <dd class="font-mono text-slate-700 text-xs">{{ $staffRole->slug }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Staff Assigned</dt>
                        <dd class="font-semibold text-slate-700">{{ $staffRole->users_count }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Modules</dt>
                        <dd class="font-semibold text-slate-700">{{ $staffRole->permission_summary }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Created by</dt>
                        <dd class="text-slate-700">{{ $staffRole->creator?->name ?? 'System' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Created</dt>
                        <dd class="text-slate-700">{{ $staffRole->created_at->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Last updated</dt>
                        <dd class="text-slate-700">{{ $staffRole->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Danger zone --}}
            @if($staffRole->users_count === 0)
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-red-700 mb-2">Danger Zone</h3>
                <p class="text-xs text-red-600 mb-3">Permanently deletes this role. Cannot be undone.</p>
                <form method="POST" action="{{ route('ceo.staff-roles.destroy', $staffRole) }}"
                      onsubmit="return confirm('Delete role \'{{ $staffRole->name }}\'? This is permanent.')">
                    @csrf @method('DELETE')
                    <button class="w-full text-xs bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg transition">Delete Role</button>
                </form>
            </div>
            @endif

            {{-- Audit Log --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Audit History</h3>
                @if($auditLogs->isEmpty())
                <p class="text-xs text-slate-400">No audit records.</p>
                @else
                <div class="space-y-2.5">
                    @foreach($auditLogs as $log)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 w-2 h-2 rounded-full flex-shrink-0 bg-{{ $log->action_color }}-400"></span>
                        <div>
                            <p class="text-xs font-semibold text-slate-700">{{ $log->action_label }}</p>
                            <p class="text-xs text-slate-400">{{ $log->actor?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
