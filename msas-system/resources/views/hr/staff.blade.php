<x-app-layout>
    <x-slot name="header">Staff Directory</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-blue-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Human Resources</p>
                <h1 class="text-2xl font-extrabold">Staff Directory</h1>
                <p class="text-blue-100 text-sm mt-1">{{ $totalStaff }} total &mdash; {{ $activeStaff }} active</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('hr.attendance') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">
                    &#9654; Attendance
                </a>
                <a href="{{ route('hr.leaves') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">
                    &#9654; Leave Requests
                </a>
                <a href="{{ route('hr.payroll') }}" class="px-4 py-2 bg-[#F4A300] hover:bg-[#d4900a] text-white rounded-xl text-sm font-semibold transition">
                    &#9654; Payroll
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">
            &#10003; {{ session('success') }}
        </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('hr.staff') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-bold text-slate-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..."
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-bold text-slate-500 mb-1">Role</label>
                <select name="role" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucwords(str_replace('-',' ',$r)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold hover:bg-[#047857] transition">
                Filter
            </button>
            <a href="{{ route('hr.staff') }}" class="px-5 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-200 transition">
                Reset
            </a>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-6 py-4">Staff Member</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Contact</th>
                            <th class="px-6 py-4">State</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($staff as $member)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $member->avatarUrl }}" class="w-9 h-9 rounded-full object-cover" alt="">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $member->name ?: $member->email }}</div>
                                        <div class="text-xs text-slate-400">ID #{{ str_pad($member->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                    {{ $member->roleLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                <div>{{ $member->email }}</div>
                                <div class="text-slate-400">{{ $member->phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">{{ $member->state ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if($member->is_active)
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">&#10003; Active</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">&#10005; Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('hr.staff.toggle', $member) }}" onsubmit="return confirm('Toggle status for {{ addslashes($member->name) }}?')">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1 text-xs font-semibold rounded-lg {{ $member->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} transition">
                                        {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">No staff records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $staff->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
