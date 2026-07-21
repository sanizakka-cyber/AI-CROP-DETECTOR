<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('ceo.users.show', $user) }}" class="text-slate-400 hover:text-slate-600 transition">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-extrabold text-xl text-gray-800">Edit User</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $user->name ?: $user->email }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">

        @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-semibold">⚠ {{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('ceo.users.update', $user) }}" class="space-y-5">
            @csrf @method('PATCH')

            {{-- Name --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-5 text-sm uppercase tracking-wide">Name</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-5 text-sm uppercase tracking-wide">Contact</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                </div>
            </div>

            {{-- Role & Location --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-5 text-sm uppercase tracking-wide">Role & Location</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Role <span class="text-red-500">*</span></label>
                        <select name="role" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                            @foreach($allRoles as $r)
                            <option value="{{ $r }}" {{ old('role', $user->role) === $r ? 'selected' : '' }}>{{ ucwords(str_replace('-',' ',$r)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">State</label>
                        <input type="text" name="state" value="{{ old('state', $user->state) }}"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">LGA</label>
                        <input type="text" name="lga" value="{{ old('lga', $user->lga) }}"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                    </div>
                </div>
            </div>

            {{-- Account Flags --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-5 text-sm uppercase tracking-wide">Account Status</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 rounded accent-[#0F6B3E]">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Account Active</p>
                            <p class="text-xs text-slate-400 mt-0.5">User can log in and use the platform</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="is_verified" value="0">
                        <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 rounded accent-[#0F6B3E]">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Verified</p>
                            <p class="text-xs text-slate-400 mt-0.5">Identity / credentials confirmed</p>
                        </div>
                    </label>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Application Status</label>
                        <select name="application_status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]/30">
                            <option value="">— None —</option>
                            <option value="pending"  {{ old('application_status', $user->application_status) === 'pending'  ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ old('application_status', $user->application_status) === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ old('application_status', $user->application_status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-6 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-bold hover:bg-[#047857] transition shadow-sm">
                    Save Changes
                </button>
                <a href="{{ route('ceo.users.show', $user) }}"
                   class="px-6 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</x-app-layout>
