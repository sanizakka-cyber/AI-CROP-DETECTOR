<x-app-layout>
    <x-slot name="header">Leave Requests</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-blue-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-blue-200 text-sm mb-1">Human Resources</p>
                <h1 class="text-2xl font-extrabold">Leave Requests</h1>
                <p class="text-blue-100 text-sm mt-1">{{ $pendingCount }} pending approval &mdash; {{ $approvedThisMonth }} approved this month</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('hr.staff') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Staff</a>
                <a href="{{ route('hr.attendance') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; Attendance</a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('hr.leaves') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Status</label>
                <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    <option value="">All Status</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Type</label>
                <select name="type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F6B3E]">
                    <option value="">All Types</option>
                    <option value="Annual"   {{ request('type') === 'Annual'   ? 'selected' : '' }}>Annual</option>
                    <option value="Sick"     {{ request('type') === 'Sick'     ? 'selected' : '' }}>Sick</option>
                    <option value="Casual"   {{ request('type') === 'Casual'   ? 'selected' : '' }}>Casual</option>
                    <option value="Movement" {{ request('type') === 'Movement' ? 'selected' : '' }}>Movement</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-semibold hover:bg-[#047857] transition">Filter</button>
            <a href="{{ route('hr.leaves') }}" class="px-5 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-semibold hover:bg-slate-200 transition">Reset</a>
        </form>

        {{-- Leave Requests Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="px-6 py-4">Staff Member</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Duration</th>
                            <th class="px-6 py-4">Reason</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($leaves as $leave)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800">{{ optional($leave->user)->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-400">{{ optional($leave->user)->roleLabel }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">{{ $leave->type }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} &rarr; {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                <div class="text-slate-400">{{ \Carbon\Carbon::parse($leave->start_date)->diffInDays($leave->end_date) + 1 }} days</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 max-w-[180px]">
                                <div class="truncate" title="{{ $leave->reason }}">{{ $leave->reason }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                    {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($leave->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($leave->status === 'pending')
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('hr.leaves.approve', $leave) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('hr.leaves.reject', $leave) }}" onsubmit="return confirm('Reject this leave request?')">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="Rejected by HR">
                                        <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs font-semibold hover:bg-red-600 transition">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                                @else
                                    <span class="text-xs text-slate-400">{{ $leave->admin_note ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">No leave requests found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">{{ $leaves->links() }}</div>
        </div>

    </div>
</x-app-layout>
