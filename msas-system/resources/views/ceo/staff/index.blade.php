<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Staff Management</h2>
            <p class="text-sm text-slate-500 mt-0.5">Create, manage, and assign roles to staff accounts</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('ceo.staff-roles.index') }}"
               class="text-sm bg-white border border-slate-200 hover:border-emerald-400 text-slate-700 font-semibold px-4 py-2 rounded-xl transition">
                Manage Roles
            </a>
            <a href="{{ route('ceo.staff.create') }}"
               class="text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-xl shadow transition">
                + Add Staff
            </a>
        </div>
    </div>
</x-slot>

<div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([['Total Staff','total','slate'],['Active','active','emerald'],['Inactive','inactive','rose'],['With Custom Role','with_custom_role','sky']] as [$label,$key,$color])
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-3xl font-bold text-{{ $color }}-600 mt-1">{{ number_format($stats[$key]) }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input name="search" value="{{ request('search') }}" placeholder="Name or email..."
               class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400 w-56">
        <select name="role" class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
            <option value="">All System Roles</option>
            @foreach($roles->pluck('name', 'slug') ?? [] as $slug => $name)
            @endforeach
            @php
            $sysRoles = ['admin'=>'Administrator','vet'=>'Veterinarian','agronomist'=>'Agronomist','hr'=>'HR Officer','finance'=>'Finance Officer','operations'=>'Operations Manager','customer-support'=>'Customer Support','field-officer'=>'Field Officer','data-analyst'=>'Data Analyst','m-e-officer'=>'M&E Officer','extension-officer'=>'Extension Officer'];
            @endphp
            @foreach($sysRoles as $r => $l)
            <option value="{{ $r }}" @selected(request('role') === $r)>{{ $l }}</option>
            @endforeach
        </select>
        <select name="status" class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
            <option value="">All Statuses</option>
            <option value="active"   @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
        @if($departments->isNotEmpty())
        <select name="department" class="border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="bg-emerald-600 text-white text-sm px-4 py-2 rounded-xl font-semibold hover:bg-emerald-700 transition">Filter</button>
        @if(request()->hasAny(['search','role','status','department']))
        <a href="{{ route('ceo.staff.index') }}" class="text-sm text-slate-500 hover:text-red-600 transition px-2 py-2">Clear</a>
        @endif
    </form>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Staff Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        @if($staff->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <p class="text-lg font-semibold">No staff found</p>
            <p class="text-sm mt-1">
                @if(request()->hasAny(['search','role','status','department']))
                    Try clearing your filters.
                @else
                    <a href="{{ route('ceo.staff.create') }}" class="text-emerald-600 hover:underline">Add your first staff member</a>
                @endif
            </p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Staff Member</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">System Role</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Custom Roles</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Department</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($staff as $member)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $member->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            <div>
                                <a href="{{ route('ceo.staff.show', $member) }}" class="font-semibold text-emerald-700 hover:underline">{{ $member->name }}</a>
                                <p class="text-xs text-slate-400">{{ $member->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 bg-slate-100 text-slate-600 text-xs rounded-full font-medium">{{ $member->role_label }}</span>
                    </td>
                    <td class="px-5 py-3">
                        @if($member->staffRoles->isEmpty())
                        <span class="text-xs text-slate-400">None</span>
                        @else
                        <div class="flex flex-wrap gap-1">
                            @foreach($member->staffRoles->take(2) as $sr)
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-medium">{{ $sr->name }}</span>
                            @endforeach
                            @if($member->staffRoles->count() > 2)
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs rounded-full">+{{ $member->staffRoles->count() - 2 }}</span>
                            @endif
                        </div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-slate-500 text-xs">{{ $member->department ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $member->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('ceo.staff.show', $member) }}" class="text-xs text-sky-600 hover:underline font-medium">View</a>
                            <a href="{{ route('ceo.staff.edit', $member) }}" class="text-xs text-slate-600 hover:underline font-medium">Edit</a>
                            @if($member->role !== 'ceo')
                            <form method="POST" action="{{ route('ceo.staff.toggle', $member) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs {{ $member->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline font-medium">
                                    {{ $member->is_active ? 'Suspend' : 'Activate' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $staff->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
