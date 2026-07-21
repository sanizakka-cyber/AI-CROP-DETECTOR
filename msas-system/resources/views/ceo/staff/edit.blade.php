<x-app-layout>
<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('ceo.staff.show', $user) }}" class="text-slate-400 hover:text-emerald-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Edit: {{ $user->name }}</h2>
            <p class="text-sm text-slate-500 mt-0.5">Update profile, system role, and RBAC assignments</p>
        </div>
    </div>
</x-slot>

<div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('ceo.staff.update', $user) }}" class="space-y-6">
        @csrf @method('PATCH')

        {{-- Personal Info --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autofocus
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state', $user->state) }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
            </div>
        </div>

        {{-- Role & Department --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Role & Department</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">System Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400"
                        {{ $user->role === 'ceo' ? 'disabled' : '' }}>
                        @foreach($systemRoles as $key => $label)
                        <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($user->role === 'ceo')
                    <input type="hidden" name="role" value="ceo">
                    <p class="text-xs text-slate-400 mt-1">CEO system role cannot be changed.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department</label>
                    <input type="text" name="department" value="{{ old('department', $user->department) }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
            </div>
        </div>

        {{-- Custom RBAC Roles --}}
        @if($staffRoles->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Custom RBAC Roles</h3>
            @php $assignedIds = $user->staffRoles->pluck('id')->toArray(); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($staffRoles as $sRole)
                <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 transition">
                    <input type="checkbox" name="staff_role_ids[]" value="{{ $sRole->id }}"
                           @checked(in_array($sRole->id, old('staff_role_ids', $assignedIds)))
                           class="mt-0.5 w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-400">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">{{ $sRole->name }}</p>
                        @if($sRole->department)
                        <p class="text-xs text-slate-400">{{ $sRole->department }}</p>
                        @endif
                        <p class="text-xs text-slate-500 mt-0.5">{{ $sRole->permission_summary }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition">
                Save Changes
            </button>
            <a href="{{ route('ceo.staff.show', $user) }}" class="text-sm text-slate-500 hover:text-slate-700 transition px-3 py-2.5">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
