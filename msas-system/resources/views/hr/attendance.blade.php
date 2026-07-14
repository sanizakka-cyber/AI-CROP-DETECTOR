<x-app-layout>
    <x-slot name="header">Attendance Management</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-blue-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Human Resources</p>
                <h1 class="text-2xl font-extrabold">Attendance Tracking</h1>
                <p class="text-blue-100 text-sm mt-1">{{ $date->format('l, d F Y') }}</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('hr.staff') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Staff</a>
                <a href="{{ route('hr.leaves') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Leaves</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        {{-- KPIs --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-[#1FA84A]">
                <p class="text-xs font-bold text-slate-500 uppercase">Present</p>
                <p class="text-3xl font-extrabold text-[#1FA84A] mt-1">{{ $presentCount }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-red-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Absent</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1">{{ $absentCount }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Late</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-1">{{ $lateCount }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Mark Single Attendance --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Mark Attendance</h3>
                <form method="POST" action="{{ route('hr.attendance.mark') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Staff Member</label>
                        <select name="user_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                            <option value="">Select staff member...</option>
                            @foreach($staffList as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->roleLabel }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Date</label>
                            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Status</label>
                            <select name="status" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Check In</label>
                            <input type="time" name="check_in" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Check Out</label>
                            <input type="time" name="check_out" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Notes (optional)</label>
                        <input type="text" name="notes" placeholder="Reason for absence, etc." class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                        Save Attendance
                    </button>
                </form>
            </div>

            {{-- Filter & View --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">View by Date</h3>
                <form method="GET" action="{{ route('hr.attendance') }}" class="flex gap-3 mb-5">
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                        class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                        <option value="">All</option>
                        <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent"  {{ request('status') === 'absent'  ? 'selected' : '' }}>Absent</option>
                        <option value="late"    {{ request('status') === 'late'    ? 'selected' : '' }}>Late</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold">View</button>
                </form>

                <div class="overflow-y-auto max-h-80">
                    @forelse($records as $rec)
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-50 last:border-0">
                        <div class="flex items-center gap-3">
                            <img src="{{ optional($rec->user)->avatarUrl ?? 'https://ui-avatars.com/api/?name=U&background=64748b&color=fff' }}" class="w-8 h-8 rounded-full" alt="">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ optional($rec->user)->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-400">{{ $rec->check_in ? 'In: '.$rec->check_in : '' }} {{ $rec->check_out ? '/ Out: '.$rec->check_out : '' }}</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $rec->status === 'present' ? 'bg-emerald-100 text-emerald-800' : ($rec->status === 'absent' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ ucfirst($rec->status) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-center text-slate-400 text-sm py-8">No attendance records for this date.</p>
                    @endforelse
                </div>
                @if($records->hasPages())
                <div class="mt-3">{{ $records->links() }}</div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
