<x-app-layout>
    <x-slot name="header">Operations Dashboard</x-slot>

    <div class="space-y-6">

        {{-- Banner --}}
        <div class="bg-gradient-to-r from-slate-800 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-slate-400/10 rounded-full blur-3xl"></div>
            <p class="text-slate-300 text-sm mb-1">Operations Control</p>
            <h1 class="text-3xl font-extrabold">Operations Dashboard</h1>
            <p class="text-slate-200 text-sm mt-2">Platform health, user registrations, and system activity monitoring.</p>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('operations.tasks') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Task Management
                </a>
                <a href="{{ route('operations.users') }}" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                    &#9776; User Overview
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#0F6B3E]">
                <p class="text-xs font-bold text-slate-500 uppercase">Total Users</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ number_format($totalUsers) }}</p>
                <p class="text-xs text-slate-400 mt-1">Registered accounts</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#1FA84A]">
                <p class="text-xs font-bold text-slate-500 uppercase">Active Users</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ number_format($activeUsers) }}</p>
                <p class="text-xs text-slate-400 mt-1">Enabled accounts</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-[#b45309]">
                <p class="text-xs font-bold text-slate-500 uppercase">New This Week</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $newThisWeek }}</p>
                <p class="text-xs text-slate-400 mt-1">Registrations</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-l-4 border-l-blue-500">
                <p class="text-xs font-bold text-slate-500 uppercase">New This Month</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $newThisMonth }}</p>
                <p class="text-xs text-slate-400 mt-1">Registrations</p>
            </div>
        </div>

        {{-- System Health --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#1FA84A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">System Uptime</p>
                    <p class="text-2xl font-extrabold text-[#0F6B3E]">{{ $systemUptime }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Consultations</p>
                    <p class="text-2xl font-extrabold text-indigo-600">{{ number_format($totalConsultations) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#b45309]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Total Animals</p>
                    <p class="text-2xl font-extrabold text-[#b45309]">{{ number_format($totalAnimals) }}</p>
                </div>
            </div>
        </div>

        {{-- Recent Registrations --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent User Registrations</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                            <th class="pb-3 pr-4">Name</th>
                            <th class="pb-3 pr-4">Email</th>
                            <th class="pb-3 pr-4">Role</th>
                            <th class="pb-3 pr-4">State</th>
                            <th class="pb-3">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentRegistrations as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="py-3 pr-4 text-slate-600 text-xs">{{ $user->email }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold capitalize">
                                    {{ str_replace('-', ' ', $user->role) }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-slate-600 text-xs">{{ $user->state ?? 'N/A' }}</td>
                            <td class="py-3 text-slate-500 text-xs">{{ $user->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">No registrations found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
