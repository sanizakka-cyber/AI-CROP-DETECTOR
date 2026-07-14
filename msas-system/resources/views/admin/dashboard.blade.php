<x-app-layout>
    <x-slot name="header">Admin Dashboard</x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <div class="space-y-6">

        {{-- Welcome Banner --}}
        <div class="rounded-2xl p-8 text-white shadow-lg relative overflow-hidden" style="background:linear-gradient(135deg,#0B2447,#0F6B3E);">
            <div class="absolute right-0 top-0 w-64 h-64 rounded-full blur-3xl" style="background:rgba(31,168,74,0.15);"></div>
            <p class="text-sm mb-1" style="color:rgba(255,255,255,0.7);">System Administration</p>
            <h1 class="text-3xl font-extrabold">Admin Dashboard</h1>
            <p class="text-sm mt-2" style="color:rgba(255,255,255,0.8);">MSAS FarmAI Platform &mdash; Full system oversight</p>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Users</p>
                <p class="text-4xl font-extrabold text-[#0F6B3E] mt-2">{{ number_format($totalUsers) }}</p>
                <p class="text-xs text-slate-400 mt-1">Registered on platform</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Users</p>
                <p class="text-4xl font-extrabold text-[#1FA84A] mt-2">{{ number_format($activeUsers) }}</p>
                <p class="text-xs text-slate-400 mt-1">Accounts active</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Approvals</p>
                <p class="text-4xl font-extrabold text-[#b45309] mt-2">{{ $pendingApprovals }}</p>
                <p class="text-xs text-slate-400 mt-1">Experts awaiting verification</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">New This Month</p>
                <p class="text-4xl font-extrabold text-blue-600 mt-2">{{ $newThisMonth }}</p>
                <p class="text-xs text-slate-400 mt-1">Registrations</p>
            </div>
        </div>

        {{-- Secondary KPIs --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Animals</p>
                <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ number_format($totalAnimals) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Consultations</p>
                <p class="text-3xl font-extrabold text-indigo-700 mt-2">{{ number_format($totalConsults) }}</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('admin.users') }}" class="flex flex-col items-center justify-center p-4 bg-[#0F6B3E]/5 hover:bg-[#0F6B3E]/10 rounded-xl transition border border-transparent hover:border-[#10b981] group">
                    <svg class="w-8 h-8 text-[#0F6B3E] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="text-sm font-semibold text-[#0F6B3E]">User Management</span>
                </a>
                <a href="{{ route('admin.staff') }}" class="flex flex-col items-center justify-center p-4 bg-amber-50 hover:bg-amber-100 rounded-xl transition border border-transparent hover:border-[#b45309] group">
                    <svg class="w-8 h-8 text-[#b45309] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="text-sm font-semibold text-[#b45309]">Staff Records</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition border border-transparent hover:border-slate-400 group">
                    <svg class="w-8 h-8 text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-sm font-semibold text-slate-600">System Settings</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex flex-col items-center justify-center p-4 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition border border-transparent hover:border-indigo-400 group">
                    <svg class="w-8 h-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="text-sm font-semibold text-indigo-600">System Reports</span>
                </a>
            </div>
        </div>

        {{-- Registration Trend Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18"/></svg>
                User Registration Trend (6 Months)
            </h3>
            <div class="relative h-48">
                <canvas id="adminRegChart"></canvas>
            </div>
        </div>

        {{-- Users by Role & Recent Registrations --}}
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Users by Role --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Users by Role</h3>
                <div class="space-y-3">
                    @forelse($usersByRole as $role => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700 capitalize">{{ str_replace('-', ' ', $role) }}</span>
                        <div class="flex items-center gap-3">
                            <div class="w-32 bg-slate-100 rounded-full h-2">
                                <div class="bg-[#1FA84A] h-2 rounded-full" style="width: {{ $totalUsers > 0 ? min(100, round($count / $totalUsers * 100)) : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-bold text-[#0F6B3E] w-8 text-right">{{ $count }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-500 text-sm text-center py-4">No role data available.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Registrations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Recent Registrations</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase">
                                <th class="pb-2">Name</th>
                                <th class="pb-2">Role</th>
                                <th class="pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($recentUsers as $u)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2 font-medium text-slate-800">{{ $u->name ?: $u->email }}</td>
                                <td class="py-2"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">{{ $u->roleLabel }}</span></td>
                                <td class="py-2">
                                    @if($u->is_active)
                                        <span class="text-emerald-600 text-xs font-bold">&#10003; Active</span>
                                    @else
                                        <span class="text-red-500 text-xs font-bold">&#10005; Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-slate-400 py-4">No users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="{{ route('admin.users') }}" class="text-[#1FA84A] text-sm font-semibold hover:underline">View all users &rarr;</a>
                </div>
            </div>
        </div>

    </div>

    <script>
    new Chart(document.getElementById('adminRegChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyGrowth->pluck('label')) !!},
            datasets: [{
                label: 'New Users',
                data: {!! json_encode($monthlyGrowth->pluck('users')) !!},
                borderColor: '#0F6B3E', backgroundColor: 'rgba(15,107,62,0.08)',
                tension: 0.4, fill: true, pointRadius: 5, pointBackgroundColor: '#0F6B3E', pointBorderColor: '#fff', pointBorderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 11 } }, grid: { display: false } } } }
    });
    </script>
</x-app-layout>
