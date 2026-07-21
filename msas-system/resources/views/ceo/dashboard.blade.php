<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-gray-800">Executive Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">MSAS FarmAI — Real-time platform intelligence</p>
            </div>
            <span id="live-clock" class="text-sm font-semibold text-slate-500 tabular-nums"></span>
        </div>
    </x-slot>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

        {{-- ── Welcome Banner ── --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#064e3b] via-[#0F6B3E] to-[#047857] text-white p-8 shadow-lg">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/5"></div>
            <div class="absolute -right-4 -bottom-12 w-40 h-40 rounded-full bg-white/5"></div>
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-emerald-200 text-sm font-medium mb-1">Chief Executive Officer</p>
                    <h1 class="text-3xl font-extrabold tracking-tight">Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->displayFirstName }}</h1>
                    <p class="text-emerald-100 text-sm mt-1">{{ now()->format('l, d F Y') }} &mdash; Here is your platform overview</p>
                </div>
                <div class="flex gap-4 flex-wrap">
                    <div class="bg-white/10 backdrop-blur rounded-xl px-5 py-3 text-center">
                        <div class="text-2xl font-black">{{ $platformHealth }}%</div>
                        <div class="text-xs text-emerald-200 mt-0.5">Platform Health</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl px-5 py-3 text-center">
                        <div class="text-2xl font-black">{{ $resolutionRate }}%</div>
                        <div class="text-xs text-emerald-200 mt-0.5">Case Resolution</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl px-5 py-3 text-center">
                        <div class="text-2xl font-black">{{ $activePct }}%</div>
                        <div class="text-xs text-emerald-200 mt-0.5">Active Users</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── KPI Cards ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
            $kpis = [
                ['label'=>'Total Users',      'value'=>number_format($totalUsers),      'sub'=>number_format($activeUsers).' active',         'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',                                                                              'bg'=>'bg-emerald-50','ic'=>'text-emerald-600','num'=>'text-emerald-700','border'=>'border-l-emerald-500'],
                ['label'=>'Net Profit',       'value'=>'₦'.number_format($netProfit),   'sub'=>($revenueGrowth>=0?'↑':'↓').abs($revenueGrowth).'% vs last month', 'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                     'bg'=>'bg-blue-50', 'ic'=>'text-blue-600','num'=>'text-blue-700','border'=>'border-l-blue-500'],
                ['label'=>'Diagnoses',        'value'=>number_format($totalDiagnoses),  'sub'=>$pendingConsults.' pending',                   'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',                                                                                                                   'bg'=>'bg-indigo-50','ic'=>'text-indigo-600','num'=>'text-indigo-700','border'=>'border-l-indigo-500'],
                ['label'=>'Livestock',        'value'=>number_format($totalAnimals),    'sub'=>'All registered animals',                      'icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',                                                                                                                                            'bg'=>'bg-amber-50','ic'=>'text-amber-600','num'=>'text-amber-700','border'=>'border-l-amber-500'],
            ];
            @endphp
            @foreach($kpis as $k)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 border-l-4 {{ $k['border'] }} p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $k['label'] }}</p>
                        <p class="text-3xl font-black {{ $k['num'] }} mt-1 leading-none">{{ $k['value'] }}</p>
                        <p class="text-xs text-gray-400 mt-1.5">{{ $k['sub'] }}</p>
                    </div>
                    <div class="w-10 h-10 {{ $k['bg'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $k['ic'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── Charts Row 1: User Growth + Revenue ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- User Growth Line Chart --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">User Growth (6 Months)</h3>
                    <div class="flex gap-3 text-xs font-semibold">
                        <span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-emerald-500 inline-block"></span>Farmers</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-blue-500 inline-block"></span>Experts</span>
                    </div>
                </div>
                <div class="relative h-52">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            {{-- Revenue Bar Chart --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">Revenue vs Expenses (6 Months)</h3>
                    <div class="flex gap-3 text-xs font-semibold">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span>Income</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-400 inline-block"></span>Expenses</span>
                    </div>
                </div>
                <div class="relative h-52">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Charts Row 2: Diagnosis Split + Users by Role + State Activity ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Diagnosis Donut --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center">
                <h3 class="font-bold text-gray-800 mb-4 self-start">Diagnosis Split</h3>
                <div class="relative h-44 w-44">
                    <canvas id="diagnosisDonut"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-2xl font-black text-gray-800">{{ number_format($totalDiagnoses) }}</span>
                        <span class="text-xs text-gray-400">Total</span>
                    </div>
                </div>
                <div class="flex gap-4 mt-4 text-xs font-semibold">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>Crop ({{ $cropDiagnoses }})</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>Livestock ({{ $livestockDiagnoses }})</span>
                </div>
            </div>

            {{-- Users by Role Bars --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Users by Role</h3>
                @php
                $roleColors = ['farmer'=>'bg-emerald-500','vet'=>'bg-blue-500','agronomist'=>'bg-teal-500','admin'=>'bg-red-500','agro-dealer'=>'bg-amber-500','extension-officer'=>'bg-indigo-500','ceo'=>'bg-purple-600','finance'=>'bg-pink-500','hr'=>'bg-orange-500'];
                $total = $usersByRole->sum();
                @endphp
                <div class="space-y-2.5">
                    @foreach($usersByRole as $role => $cnt)
                    @php $pct = $total > 0 ? round(($cnt/$total)*100) : 0; $bar = $roleColors[$role] ?? 'bg-gray-400'; @endphp
                    <div>
                        <div class="flex justify-between text-xs font-medium text-gray-600 mb-0.5">
                            <span class="capitalize">{{ str_replace('-',' ',$role) }}</span>
                            <span class="font-bold">{{ $cnt }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $bar }} rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- State Activity --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Top States by Users</h3>
                @if(!empty($stateActivity))
                @php $maxState = max($stateActivity ?: [1]); @endphp
                <div class="space-y-2.5">
                    @foreach($stateActivity as $state => $cnt)
                    @php $pct = $maxState > 0 ? round(($cnt/$maxState)*100) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs font-medium text-gray-600 mb-0.5">
                            <span>{{ $state }}</span>
                            <span class="font-bold">{{ $cnt }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-400 rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-32 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span class="text-sm">No state data yet</span>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Platform KPI Gauges ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-5 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Platform Performance Indicators
            </h3>
            @php
            $gauges = [
                ['label'=>'Platform Health',       'value'=>$platformHealth,  'target'=>90,  'color'=>'bg-emerald-500'],
                ['label'=>'Case Resolution Rate',  'value'=>$resolutionRate,  'target'=>85,  'color'=>'bg-blue-500'],
                ['label'=>'Active User Rate',       'value'=>$activePct,       'target'=>80,  'color'=>'bg-teal-500'],
                ['label'=>'Expert Approval Pending','value'=>min(100, $pendingExperts*10), 'target'=>10, 'color'=>'bg-amber-500', 'invert'=>true],
                ['label'=>'Market Listings Active', 'value'=>min(100,$marketItems*5),     'target'=>50,  'color'=>'bg-indigo-500'],
            ];
            @endphp
            <div class="space-y-3">
                @foreach($gauges as $g)
                @php $pct = min(100, $g['value']); $onTarget = isset($g['invert']) ? $pct <= $g['target'] : $pct >= $g['target']; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">{{ $g['label'] }}</span>
                        <span class="font-bold {{ $onTarget ? 'text-emerald-600' : 'text-amber-500' }}">{{ $g['value'] }}%
                            <span class="text-xs text-gray-400 font-normal">/ {{ $g['target'] }}% target</span>
                        </span>
                    </div>
                    <div class="relative h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="absolute h-full {{ $g['color'] }} rounded-full transition-all" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Quick Actions + Recent Users ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Quick Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    @php
                    $actions = [
                        ['href'=>route('ceo.users'),   'label'=>'Manage Users',     'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'bg'=>'bg-emerald-50','ic'=>'text-emerald-600'],
                        ['href'=>route('ceo.reports'), 'label'=>'Reports',          'icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'bg'=>'bg-blue-50','ic'=>'text-blue-600'],
                        ['href'=>route('admin.users', ['role'=>'vet']), 'label'=>'Approvals ('.$pendingExperts.')', 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'bg'=>'bg-amber-50','ic'=>'text-amber-600'],
                        ['href'=>route('marketplace'),  'label'=>'Market ('.$marketItems.')', 'icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'bg'=>'bg-indigo-50','ic'=>'text-indigo-600'],
                    ];
                    @endphp
                    @foreach($actions as $a)
                    <a href="{{ $a['href'] }}" class="flex flex-col items-center gap-2 p-3 {{ $a['bg'] }} rounded-xl hover:brightness-95 transition text-center border border-transparent hover:border-gray-200">
                        <div class="w-9 h-9 bg-white rounded-lg shadow-sm flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $a['ic'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $a['icon'] }}"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 leading-tight">{{ $a['label'] }}</span>
                    </a>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                    <div class="flex justify-between text-xs"><span class="text-gray-500">Staff present today</span><span class="font-bold text-gray-800">{{ $presentToday }} / {{ $staffCount }}</span></div>
                    <div class="flex justify-between text-xs"><span class="text-gray-500">Pending leave requests</span><span class="font-bold {{ $pendingLeaves > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $pendingLeaves }}</span></div>
                    <div class="flex justify-between text-xs"><span class="text-gray-500">Pending marketplace</span><span class="font-bold {{ $pendingListings > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $pendingListings }}</span></div>
                </div>
            </div>

            {{-- Recent Registrations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">Recent Registrations</h3>
                    <a href="{{ route('ceo.users') }}" class="text-xs font-semibold text-emerald-600 hover:underline">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-4">Name</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-4">Role</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-4">State</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-4">Joined</th>
                                <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($recentUsers as $u)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="py-2.5 pr-4 font-semibold text-gray-800">{{ $u->first_name }} {{ $u->last_name }}</td>
                                <td class="py-2.5 pr-4"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold capitalize">{{ str_replace('-',' ',$u->role) }}</span></td>
                                <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $u->state ?? '—' }}</td>
                                <td class="py-2.5 pr-4 text-gray-400 text-xs">{{ $u->created_at->format('d M Y') }}</td>
                                <td class="py-2.5">
                                    @if($u->is_active)
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1"></span><span class="text-xs text-emerald-600 font-semibold">Active</span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-red-400 inline-block mr-1"></span><span class="text-xs text-red-500 font-semibold">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="py-8 text-center text-gray-300 text-sm">No users yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Disease Alerts ── --}}
        @if(!empty($diseaseAlerts))
        <div class="bg-white rounded-2xl shadow-sm border border-amber-200 p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Active Disease Alerts
            </h3>
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($diseaseAlerts as $a)
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-start gap-3">
                    <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-amber-900 text-sm">{{ $a['disease'] }}</p>
                        <p class="text-xs text-amber-700 mt-0.5">{{ $a['cases'] }} {{ Str::plural('case', $a['cases']) }} reported (last 30 days)</p>
                        <span class="inline-block mt-1 text-xs font-semibold bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full capitalize">
                            {{ $a['type'] ?? 'Unknown' }} disease
                        </span>
                    </div>
                    <span class="ml-auto text-xs font-bold px-2 py-1 rounded-full {{ $a['severity'] === 'high' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }} capitalize">{{ $a['severity'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    <script>
    // Live clock
    function tick() {
        const now = new Date();
        document.getElementById('live-clock').textContent = now.toLocaleTimeString('en-NG', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    tick(); setInterval(tick, 1000);

    // User Growth Chart
    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyGrowth->pluck('label')) !!},
            datasets: [
                { label: 'Farmers', data: {!! json_encode($monthlyGrowth->pluck('farmers')) !!}, borderColor: '#1FA84A', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#1FA84A' },
                { label: 'Experts', data: {!! json_encode($monthlyGrowth->pluck('experts')) !!}, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.06)', tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#3b82f6' },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 11 } }, grid: { display: false } } } }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($revenueChart->pluck('month')) !!},
            datasets: [
                { label: 'Income',   data: {!! json_encode($revenueChart->pluck('income')) !!},  backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 6 },
                { label: 'Expenses', data: {!! json_encode($revenueChart->pluck('expense')) !!}, backgroundColor: 'rgba(248,113,113,0.75)', borderRadius: 6 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 11 } }, grid: { display: false } } } }
    });

    // Diagnosis Donut
    const total = {{ $totalDiagnoses }};
    new Chart(document.getElementById('diagnosisDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Crop', 'Livestock'],
            datasets: [{ data: [{{ $cropDiagnoses ?: ($total > 0 ? max(1,$total-1) : 1) }}, {{ $livestockDiagnoses ?: 1 }}], backgroundColor: ['#1FA84A','#f59e0b'], borderWidth: 0, hoverOffset: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } } }
    });
    </script>
</x-app-layout>
