@php
    use App\Data\NigeriaLocations;
    $nigeriaStates = NigeriaLocations::states();
@endphp
<x-app-layout>
<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('ceo.staff.index') }}" class="text-slate-400 hover:text-emerald-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Add Staff Member</h2>
            <p class="text-sm text-slate-500 mt-0.5">Create an account and optionally assign custom RBAC roles</p>
        </div>
    </div>
</x-slot>

<div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('ceo.staff.store') }}" class="space-y-6">
        @csrf

        {{-- Personal Info --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required autofocus
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400" placeholder="080XXXXXXXX">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{
                    states: @json($nigeriaStates),
                    state: '{{ old('state', '') }}',
                    lga: '{{ old('lga', '') }}',
                    get lgas() { return this.states.find(s => s.name === this.state)?.lgas ?? []; },
                }">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">State</label>
                    <select name="state" x-model="state" @change="lga = ''"
                            class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                        <option value="">Select state...</option>
                        <template x-for="s in states" :key="s.name">
                            <option :value="s.name" x-text="s.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">LGA</label>
                    <select name="lga" x-model="lga" :disabled="!state"
                            class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400 disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="" x-text="state ? 'Select LGA...' : 'Select a state first'"></option>
                        <template x-for="l in lgas" :key="l">
                            <option :value="l" x-text="l"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        {{-- Role & Department --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Role & Department</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">System Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                        <option value="">Select a role...</option>
                        @foreach($systemRoles as $key => $label)
                        <option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Determines dashboard and base access level.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department</label>
                    <select name="department" class="w-full border-slate-200 rounded-xl text-sm focus:ring-emerald-400">
                        <option value="">Select department...</option>
                        @foreach($departmentOptions as $dept)
                        <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Custom RBAC Roles --}}
        @if($staffRoles->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Custom RBAC Roles (Optional)</h3>
            <p class="text-xs text-slate-500 mb-4">Assign one or more custom roles to grant granular module permissions. Staff can have multiple roles.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($staffRoles as $sRole)
                <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 transition
                       {{ in_array($sRole->id, old('staff_role_ids', [])) ? 'border-emerald-400 bg-emerald-50' : '' }}">
                    <input type="checkbox" name="staff_role_ids[]" value="{{ $sRole->id }}"
                           @checked(in_array($sRole->id, old('staff_role_ids', [])) || request('role') == $sRole->id)
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

        {{-- Password Notice --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
            <strong>Account Setup:</strong> No password is set here. A secure, one-time "set your password" link will be emailed
            to the staff member — it expires in 7 days and can only be used once. They'll choose their own password and be
            required to keep it confidential.
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition">
                Create Staff Account
            </button>
            <a href="{{ route('ceo.staff.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition px-3 py-2.5">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
