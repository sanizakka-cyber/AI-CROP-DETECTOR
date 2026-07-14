<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">Data Analyst Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Platform analytics, trends & insights</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Data Analyst
            </span>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

        {{-- Hero Banner --}}
        <div class="bg-gradient-to-r from-[#0B2447] to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-400/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Data & Analytics</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-emerald-100 text-sm mt-2">Platform analytics, user trends, and performance insights.</p>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('ceo.reports.generate', 'users') }}" class="px-5 py-2.5 bg-[#6366f1] text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                    &#9776; User Report
                </a>
                <a href="{{ route('ceo.reports.generate', 'consultations') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Consultation Report
                </a>
                <a href="{{ route('diagnostics.history') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9776; Diagnosis History
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
            $cards = [
                ['label'=>'Total Users',       'value'=>number_format($totalUsers),      'trend'=>'+12%', 'up'=>true, 'color'=>'blue',    'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['label'=>'Diagnoses',         'value'=>number_format($totalConsults),   'trend'=>'+8%',  'up'=>true, 'color'=>'green',   'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label'=>'Animals Tracked',   'value'=>number_format($totalAnimals),    'trend'=>'+5%',  'up'=>true, 'color'=>'amber',   'icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                ['label'=>'New This Month',    'value'=>number_format($activeThisMonth), 'trend'=>'+3%',  'up'=>true, 'color'=>'emerald', 'icon'=>'M13 10V3L4 14h7v7l9-11h-7z'],
            ];
            $clr = [
                'blue'   =>['bg'=>'bg-blue-50',   'ic'=>'text-blue-500',   'num'=>'text-blue-700',   'border'=>'border-l-blue-400'],
                'green'  =>['bg'=>'bg-green-50',  'ic'=>'text-green-500',  'num'=>'text-green-700',  'border'=>'border-l-green-400'],
                'amber'  =>['bg'=>'bg-amber-50',  'ic'=>'text-amber-500',  'num'=>'text-amber-700',  'border'=>'border-l-amber-400'],
                'emerald'=>['bg'=>'bg-emerald-50','ic'=>'text-emerald-500','num'=>'text-emerald-700','border'=>'border-l-emerald-400'],
            ];
            @endphp
            @foreach($cards as $c)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 border-l-4 {{ $clr[$c['color']]['border'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $c['label'] }}</p>
                        <p class="text-3xl font-black {{ $clr[$c['color']]['num'] }} mt-1 leading-none">{{ $c['value'] }}</p>
                        <p class="text-xs mt-1.5 {{ $c['up'] ? 'text-emerald-500' : 'text-red-500' }} font-semibold">{{ $c['up'] ? '↑' : '↓' }} {{ $c['trend'] }} this month</p>
                    </div>
                    <div class="w-10 h-10 {{ $clr[$c['color']]['bg'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $clr[$c['color']]['ic'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Registration Trend Line --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    Registration Trend (6 Months)
                </h3>
                <div class="relative h-52">
                    <canvas id="regTrendChart"></canvas>
                </div>
            </div>

            {{-- User Role Distribution Donut --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    User Role Distribution
                </h3>
                <div class="flex items-center gap-6">
                    <div class="relative h-44 w-44 flex-shrink-0">
                        <canvas id="roleDonut"></canvas>
                    </div>
                    <div class="space-y-2 flex-1 min-w-0">
                        @php
                        $roleColors2 = ['farmer'=>'#1FA84A','vet'=>'#3b82f6','agronomist'=>'#14b8a6','admin'=>'#ef4444','agro-dealer'=>'#f59e0b','extension-officer'=>'#6366f1','ceo'=>'#9333ea','finance'=>'#ec4899','hr'=>'#f97316'];
                        $totalR = array_sum($usersByRole);
                        @endphp
                        @foreach($usersByRole as $role => $cnt)
                        @php $pct = $totalR > 0 ? round(($cnt/$totalR)*100) : 0; $col = $roleColors2[$role] ?? '#94a3b8'; @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $col }}"></span>
                            <span class="text-gray-600 capitalize truncate flex-1">{{ str_replace('-',' ',$role) }}</span>
                            <span class="font-bold text-gray-800">{{ $cnt }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Role Bars + Diagnostic Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Platform User Bar Chart --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Users by Role (Bar View)
                </h3>
                <div class="relative h-52">
                    <canvas id="roleBarChart"></canvas>
                </div>
            </div>

            {{-- Recent Consultations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Recent Diagnostic Activity
                </h3>
                @if($recentConsults->count())
                <div class="overflow-y-auto max-h-52">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr class="border-b border-gray-100">
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-3">Farmer</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-3">Status</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentConsults as $c)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-2 pr-3 font-medium text-gray-700 text-xs">{{ $c->user->first_name ?? 'Unknown' }}</td>
                                <td class="py-2 pr-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $c->status === 'resolved' ? 'bg-green-100 text-green-700' : ($c->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                                        {{ ucfirst($c->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td class="py-2 text-gray-400 text-xs">{{ $c->created_at->format('d M') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-44 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                    <p class="text-sm">No activity yet</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    <script>
    // Registration Trend
    new Chart(document.getElementById('regTrendChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRegistrations->pluck('month')) !!},
            datasets: [{
                label: 'Registrations',
                data: {!! json_encode($monthlyRegistrations->pluck('count')) !!},
                borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)',
                tension: 0.4, fill: true, pointRadius: 5, pointBackgroundColor: '#6366f1', pointBorderColor: '#fff', pointBorderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 11 } }, grid: { display: false } } } }
    });

    // Role Donut
    new Chart(document.getElementById('roleDonut'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($usersByRole)) !!},
            datasets: [{ data: {!! json_encode(array_values($usersByRole)) !!}, backgroundColor: ['#1FA84A','#3b82f6','#14b8a6','#ef4444','#f59e0b','#6366f1','#9333ea','#ec4899','#f97316'], borderWidth: 0, hoverOffset: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    // Role Bar Chart
    new Chart(document.getElementById('roleBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($r) => str_replace('-',' ',$r), array_keys($usersByRole))) !!},
            datasets: [{ label: 'Users', data: {!! json_encode(array_values($usersByRole)) !!}, backgroundColor: ['#1FA84A','#3b82f6','#14b8a6','#ef4444','#f59e0b','#6366f1','#9333ea','#ec4899','#f97316'], borderRadius: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 10 }, maxRotation: 30 }, grid: { display: false } } } }
    });
    </script>
</x-app-layout>
