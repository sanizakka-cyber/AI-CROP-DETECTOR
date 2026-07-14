<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">M&amp;E Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Monitoring &amp; Evaluation — Impact metrics and programme performance</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                M&amp;E Officer
            </span>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

        {{-- Hero Banner --}}
        <div class="bg-gradient-to-r from-[#0B2447] to-[#1FA84A] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-400/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Monitoring &amp; Evaluation</p>
            <h1 class="text-3xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-emerald-100 text-sm mt-2">Programme performance, impact metrics, and intervention outcomes.</p>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-3">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('ceo.reports.generate', 'consultations') }}" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                    &#9776; Programme Report
                </a>
                <a href="{{ route('ceo.reports.generate', 'users') }}" class="px-5 py-2.5 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition shadow-sm">
                    &#9776; Farmer Report
                </a>
                <a href="{{ route('diagnostics.history') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9776; Diagnosis History
                </a>
                <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                    &#9998; My Profile
                </a>
            </div>
        </div>

        {{-- Impact Indicator Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
            $indicators = [
                ['title'=>'Farmers Reached',       'value'=>number_format($totalFarmers),  'change'=>'+12%', 'up'=>true, 'color'=>'emerald', 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['title'=>'Animals Monitored',     'value'=>number_format($totalAnimals),  'change'=>'+8%',  'up'=>true, 'color'=>'blue',    'icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                ['title'=>'Vet Consultations',     'value'=>number_format($totalConsults), 'change'=>'+24%', 'up'=>true, 'color'=>'teal',    'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['title'=>'Disease Interventions', 'value'=>number_format($resolvedCases), 'change'=>'94% resolved', 'up'=>true, 'color'=>'green', 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
            $colors=['emerald'=>['bg'=>'bg-emerald-50','ic'=>'text-emerald-500','num'=>'text-emerald-700','border'=>'border-l-emerald-400'],'blue'=>['bg'=>'bg-blue-50','ic'=>'text-blue-500','num'=>'text-blue-700','border'=>'border-l-blue-400'],'teal'=>['bg'=>'bg-teal-50','ic'=>'text-teal-500','num'=>'text-teal-700','border'=>'border-l-teal-400'],'green'=>['bg'=>'bg-green-50','ic'=>'text-green-500','num'=>'text-green-700','border'=>'border-l-green-400']];
            @endphp
            @foreach($indicators as $ind)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 border-l-4 {{ $colors[$ind['color']]['border'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $ind['title'] }}</p>
                        <p class="text-3xl font-black {{ $colors[$ind['color']]['num'] }} mt-1 leading-none">{{ $ind['value'] }}</p>
                        <p class="text-xs {{ $ind['up'] ? 'text-emerald-500' : 'text-red-500' }} font-semibold mt-1.5">{{ $ind['up'] ? '↑' : '↓' }} {{ $ind['change'] }}</p>
                    </div>
                    <div class="w-10 h-10 {{ $colors[$ind['color']]['bg'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $colors[$ind['color']]['ic'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ind['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- KPI Gauges + Resolution Radar --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KPI Progress Bars --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Key Performance Indicators
                </h3>
                @php
                $kpis = [
                    ['label'=>'AI Scan Adoption Rate',      'target'=>80, 'actual'=>$scanAdoptionRate,       'color'=>'bg-emerald-500'],
                    ['label'=>'Vet Response Time (<24h)',   'target'=>90, 'actual'=>$vetResponseRate,         'color'=>'bg-blue-500'],
                    ['label'=>'Farmer Retention (30-day)',  'target'=>75, 'actual'=>$farmerRetention,         'color'=>'bg-teal-500'],
                    ['label'=>'Disease Detection Accuracy', 'target'=>95, 'actual'=>$aiAccuracy,              'color'=>'bg-indigo-500'],
                    ['label'=>'Marketplace Utilisation',    'target'=>60, 'actual'=>$marketplaceUtilisation,  'color'=>'bg-amber-500'],
                ];
                @endphp
                <div class="space-y-4">
                    @foreach($kpis as $kpi)
                    @php $onTarget = $kpi['actual'] >= $kpi['target']; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $kpi['label'] }}</span>
                            <span class="font-bold {{ $onTarget ? 'text-emerald-600' : 'text-amber-500' }}">{{ $kpi['actual'] }}%
                                <span class="text-xs text-gray-400 font-normal">/ {{ $kpi['target'] }}%</span>
                            </span>
                        </div>
                        <div class="relative h-3 bg-gray-100 rounded-full overflow-visible">
                            <div class="absolute h-full {{ $kpi['color'] }} rounded-full transition-all" style="width:{{ min($kpi['actual'],100) }}%"></div>
                            <div class="absolute top-0 h-3 border-r-2 border-dashed border-gray-400" style="left:{{ $kpi['target'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-3">Dashed line = target threshold</p>
            </div>

            {{-- Programme Score Gauge --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center">
                <h3 class="font-bold text-gray-800 mb-4 self-start">Programme Score</h3>
                <div class="relative h-44 w-44">
                    <canvas id="programmeGauge"></canvas>
                    @php $score = (int) round(($scanAdoptionRate*0.25)+($vetResponseRate*0.25)+($farmerRetention*0.2)+($aiAccuracy*0.2)+($marketplaceUtilisation*0.1)); @endphp
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-3xl font-black text-gray-800">{{ $score }}</span>
                        <span class="text-xs text-gray-400 font-semibold">/ 100</span>
                    </div>
                </div>
                <p class="mt-3 text-sm font-bold {{ $score >= 75 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                    {{ $score >= 75 ? 'On Track' : ($score >= 50 ? 'Needs Attention' : 'Below Target') }}
                </p>
                <p class="text-xs text-gray-400 text-center mt-1">Composite programme performance index</p>
            </div>
        </div>

        {{-- Monthly Chart + State Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Monthly Consultations Chart --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    Monthly Programme Activity
                </h3>
                <div class="relative h-52">
                    <canvas id="monthlyActivityChart"></canvas>
                </div>
            </div>

            {{-- Geographic Reach --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Top States by Activity
                </h3>
                @if(!empty($stateActivity))
                @php $maxC = max($stateActivity ?: [1]); @endphp
                <div class="space-y-2.5">
                    @foreach($stateActivity as $state => $count)
                    @php $pct = $maxC > 0 ? round(($count/$maxC)*100) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs font-medium text-gray-600 mb-0.5">
                            <span>{{ $state }}</span>
                            <span class="font-bold">{{ $count }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-teal-400 rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-32 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <p class="text-sm">No geographic data</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Monthly Summary Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Monthly Programme Summary
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-xs font-bold text-gray-400 uppercase pb-2 pr-6">Month</th>
                            <th class="text-right text-xs font-bold text-gray-400 uppercase pb-2 pr-6">New Farmers</th>
                            <th class="text-right text-xs font-bold text-gray-400 uppercase pb-2 pr-6">Consultations</th>
                            <th class="text-right text-xs font-bold text-gray-400 uppercase pb-2">Resolution Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($monthlySummary as $row)
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-2.5 pr-6 font-semibold text-gray-700">{{ $row['month'] }}</td>
                            <td class="py-2.5 pr-6 text-right text-gray-600">{{ $row['farmers'] }}</td>
                            <td class="py-2.5 pr-6 text-right text-gray-600">{{ $row['consults'] }}</td>
                            <td class="py-2.5 text-right">
                                <span class="font-bold {{ $row['resolution_rate'] >= 80 ? 'text-emerald-600' : 'text-amber-500' }}">{{ $row['resolution_rate'] }}%</span>
                            </td>
                        </tr>
                        @endforeach
                        @if(empty($monthlySummary))
                        <tr><td colspan="4" class="py-8 text-center text-gray-300 text-sm">No data available</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
    // Programme Score Gauge (doughnut half)
    @php $score2 = (int) round(($scanAdoptionRate*0.25)+($vetResponseRate*0.25)+($farmerRetention*0.2)+($aiAccuracy*0.2)+($marketplaceUtilisation*0.1)); @endphp
    new Chart(document.getElementById('programmeGauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [{{ $score2 }}, {{ 100 - $score2 }}],
                backgroundColor: ['{{ $score2 >= 75 ? "#1FA84A" : ($score2 >= 50 ? "#f59e0b" : "#ef4444") }}', '#f1f5f9'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false }, tooltip: { enabled: false } }, rotation: -90, circumference: 180 }
    });

    // Monthly Activity Chart
    new Chart(document.getElementById('monthlyActivityChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($monthlySummary, 'month')) !!},
            datasets: [
                { label: 'New Farmers',    data: {!! json_encode(array_column($monthlySummary, 'farmers')) !!},  backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 6 },
                { label: 'Consultations',  data: {!! json_encode(array_column($monthlySummary, 'consults')) !!}, backgroundColor: 'rgba(99,102,241,0.75)',  borderRadius: 6 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 10 } }, grid: { display: false } } }
        }
    });
    </script>
</x-app-layout>
