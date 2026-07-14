<x-app-layout>
    <x-slot name="header">Human Resources Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-blue-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-blue-400/10 rounded-full blur-3xl"></div>
            <p class="text-blue-200 text-sm mb-1">Human Resources</p>
            <h1 class="text-3xl font-extrabold">HR Dashboard</h1>
            <p class="text-blue-100 text-sm mt-2">Staff management, attendance, and leave requests overview.</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Staff</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ $staffCount }}</p>
                <p class="text-xs text-slate-400 mt-1">Non-farmer accounts</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A]">
                <p class="text-xs font-bold text-slate-500 uppercase">Present Today</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ $presentToday }}</p>
                <p class="text-xs text-slate-400 mt-1">Attendance logged</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-red-500">
                <p class="text-xs font-bold text-slate-500 uppercase">Absent Today</p>
                <p class="text-4xl font-extrabold text-red-600 mt-2">{{ $absentToday }}</p>
                <p class="text-xs text-slate-400 mt-1">No check-in</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#b45309]">
                <p class="text-xs font-bold text-slate-500 uppercase">Pending Leaves</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $pendingLeaves }}</p>
                <p class="text-xs text-slate-400 mt-1">Awaiting approval</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('hr.staff') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Staff Directory
                </a>
                <a href="{{ route('hr.attendance') }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    &#9654; Attendance
                </a>
                <a href="{{ route('hr.leaves') }}" class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                    &#9654; Leave Requests
                </a>
                <a href="{{ route('hr.payroll') }}" class="px-5 py-2.5 bg-slate-700 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition shadow-sm">
                    &#9654; Payroll
                </a>
            </div>
        </div>

        {{-- Recent Staff --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Staff Directory (Recent)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Name</th>
                            <th class="pb-3 pr-4">Role</th>
                            <th class="pb-3 pr-4">State</th>
                            <th class="pb-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentStaff as $staff)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $staff->name ?: $staff->email }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                    {{ $staff->roleLabel }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-slate-600 text-xs">{{ $staff->state ?? 'N/A' }}</td>
                            <td class="py-3">
                                @if($staff->is_active)
                                    <span class="text-emerald-600 text-xs font-bold">&#10003; Active</span>
                                @else
                                    <span class="text-red-500 text-xs font-bold">&#10005; Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-sm">No staff records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('hr.staff') }}" class="text-[#1FA84A] text-sm font-semibold hover:underline">View full staff list &rarr;</a>
            </div>
        </div>

    </div>
</x-app-layout>
