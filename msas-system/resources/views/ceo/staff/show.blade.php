<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('ceo.staff.index') }}" class="text-slate-400 hover:text-emerald-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex items-center gap-3">
                <img src="{{ $user->avatar_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-slate-800">{{ $user->name }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $user->email }} · {{ $user->role_label }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('ceo.staff.edit', $user) }}"
               class="text-sm bg-white border border-slate-200 hover:border-emerald-400 text-slate-700 font-semibold px-4 py-2 rounded-xl transition">
                Edit
            </a>
            <form method="POST" action="{{ route('ceo.staff.reset-password', $user) }}"
                  onsubmit="return confirm('Reset {{ $user->first_name }}\'s password to Welcome@123? They will be required to change it immediately.')">
                @csrf @method('PATCH')
                <button class="text-sm bg-white border border-amber-300 hover:bg-amber-50 text-amber-700 font-semibold px-4 py-2 rounded-xl transition">
                    Reset Password
                </button>
            </form>
            @if($user->role !== 'ceo')
            <form method="POST" action="{{ route('ceo.staff.toggle', $user) }}">
                @csrf @method('PATCH')
                <button class="text-sm font-semibold px-4 py-2 rounded-xl border transition
                    {{ $user->is_active ? 'border-rose-300 text-rose-700 hover:bg-rose-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                    {{ $user->is_active ? 'Suspend' : 'Activate' }}
                </button>
            </form>
            @endif
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

        {{-- Left: Profile + Role Assignment --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Profile Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-base font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Staff Profile</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Staff ID</dt>
                        <dd class="font-mono text-slate-700">{{ $user->staff_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">System Role</dt>
                        <dd class="font-semibold text-slate-700">{{ $user->role_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Department</dt>
                        <dd class="text-slate-700">{{ $user->department ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Phone</dt>
                        <dd class="text-slate-700">{{ $user->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">State</dt>
                        <dd class="text-slate-700">{{ $user->state ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Member Since</dt>
                        <dd class="text-slate-700">{{ $user->created_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Last Login</dt>
                        <dd class="text-slate-700">{{ $user->last_seen ? \Carbon\Carbon::parse($user->last_seen)->diffForHumans() : 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Password Reset Required</dt>
                        <dd class="{{ $user->force_password_reset ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">
                            {{ $user->force_password_reset ? 'Yes — on next login' : 'No' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Assigned Custom Roles --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-base font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Custom RBAC Roles</h3>

                @if($user->staffRoles->isEmpty())
                <p class="text-sm text-slate-400 py-4">No custom roles assigned.</p>
                @else
                <div class="space-y-3 mb-5">
                    @foreach($user->staffRoles as $sRole)
                    <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-slate-700">{{ $sRole->name }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $sRole->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $sRole->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $sRole->permission_summary }}
                                @if($sRole->department) · {{ $sRole->department }}@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('ceo.staff-roles.show', $sRole) }}" class="text-xs text-sky-600 hover:underline">View</a>
                            <form method="POST" action="{{ route('ceo.staff.remove-role', $user) }}">
                                @csrf @method('DELETE')
                                <input type="hidden" name="staff_role_id" value="{{ $sRole->id }}">
                                <button class="text-xs text-rose-500 hover:underline font-medium"
                                        onclick="return confirm('Remove {{ $sRole->name }} from {{ $user->first_name }}?')">Remove</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Assign Additional Roles --}}
                @php $unassignedRoles = $staffRoles->whereNotIn('id', $user->staffRoles->pluck('id')); @endphp
                @if($unassignedRoles->isNotEmpty())
                <form method="POST" action="{{ route('ceo.staff.assign-roles', $user) }}" class="border-t border-slate-100 pt-4">
                    @csrf
                    <p class="text-xs font-semibold text-slate-600 mb-2">Assign Additional Role</p>
                    <div class="flex gap-2">
                        <select name="staff_role_ids[]" required
                                class="flex-1 border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                            <option value="">Select role to assign...</option>
                            @foreach($unassignedRoles as $sRole)
                            <option value="{{ $sRole->id }}">{{ $sRole->name }}{{ $sRole->department ? ' — ' . $sRole->department : '' }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="bg-emerald-600 text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-emerald-700 transition">
                            Assign
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- Right: Quick Actions + Audit Log --}}
        <div class="space-y-6">

            {{-- Quick Actions --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('ceo.staff.edit', $user) }}"
                       class="block w-full text-center text-sm bg-slate-100 hover:bg-emerald-100 text-slate-700 font-semibold py-2 rounded-xl transition">
                        Edit Profile
                    </a>
                    <form method="POST" action="{{ route('ceo.staff.reset-password', $user) }}"
                          onsubmit="return confirm('Reset password to Welcome@123?')">
                        @csrf @method('PATCH')
                        <button class="block w-full text-sm bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold py-2 rounded-xl transition">
                            Reset Password
                        </button>
                    </form>
                    @if($user->role !== 'ceo')
                    <form method="POST" action="{{ route('ceo.staff.toggle', $user) }}">
                        @csrf @method('PATCH')
                        <button class="block w-full text-sm font-semibold py-2 rounded-xl transition
                            {{ $user->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}">
                            {{ $user->is_active ? 'Suspend Account' : 'Activate Account' }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Audit Log --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">RBAC Audit History</h3>
                @if($auditLogs->isEmpty())
                <p class="text-xs text-slate-400">No RBAC audit records.</p>
                @else
                <div class="space-y-2.5 max-h-72 overflow-y-auto">
                    @foreach($auditLogs as $log)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0 bg-{{ $log->action_color }}-400"></span>
                        <div>
                            <p class="text-xs font-semibold text-slate-700">{{ $log->action_label }}</p>
                            <p class="text-xs text-slate-400">
                                @if($log->actor_id === $user->id) Self @else {{ $log->actor?->name ?? 'System' }} @endif
                                · {{ $log->created_at->diffForHumans() }}
                            </p>
                            @if($log->target_label && $log->target_type === 'StaffRole')
                            <p class="text-xs text-slate-500">{{ $log->target_label }}</p>
                            @endif
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
