<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Staff Role Management</h2>
            <p class="text-sm text-slate-500 mt-0.5">Define custom roles with granular module permissions</p>
        </div>
        <a href="{{ route('ceo.staff-roles.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-xl shadow transition">
            + New Role
        </a>
    </div>
</x-slot>

<div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([['Total Roles','total','slate'],['Active','active','emerald'],['Inactive','inactive','rose'],['Assigned','assigned','sky']] as [$label,$key,$color])
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-3xl font-bold text-{{ $color }}-600 mt-1">{{ number_format($stats[$key]) }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input name="search" value="{{ request('search') }}" placeholder="Search roles..."
               class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400 w-56">
        <select name="status" class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
            <option value="">All Statuses</option>
            <option value="active"   @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
        <button type="submit" class="bg-emerald-600 text-white text-sm px-4 py-2 rounded-xl font-semibold hover:bg-emerald-700 transition">Filter</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('ceo.staff-roles.index') }}" class="text-sm text-slate-500 hover:text-red-600 transition px-2 py-2">Clear</a>
        @endif
    </form>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Roles Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        @if($roles->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <p class="text-lg font-semibold">No roles found</p>
            <p class="text-sm mt-1">
                @if(request()->hasAny(['search','status']))
                    Try clearing your filters.
                @else
                    <a href="{{ route('ceo.staff-roles.create') }}" class="text-emerald-600 hover:underline">Create your first staff role</a>
                @endif
            </p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Role Name</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Department</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Permissions</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Staff</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($roles as $role)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <a href="{{ route('ceo.staff-roles.show', $role) }}" class="font-semibold text-emerald-700 hover:underline">{{ $role->name }}</a>
                        @if($role->description)
                        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ $role->description }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $role->department ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-500">{{ $role->permission_summary }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-700">{{ $role->users_count }}</td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                            {{ $role->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $role->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('ceo.staff-roles.edit', $role) }}"
                               class="text-xs text-sky-600 hover:underline font-medium">Edit</a>
                            <form method="POST" action="{{ route('ceo.staff-roles.toggle', $role) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs {{ $role->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline font-medium">
                                    {{ $role->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @if($role->users_count === 0)
                            <form method="POST" action="{{ route('ceo.staff-roles.destroy', $role) }}"
                                  onsubmit="return confirm('Delete role \'{{ $role->name }}\'? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-600 hover:underline font-medium">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $roles->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
