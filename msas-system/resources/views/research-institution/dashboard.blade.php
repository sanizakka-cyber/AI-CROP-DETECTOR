<x-app-layout>
    <x-slot name="header">Research Institution Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-indigo-900 to-indigo-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-indigo-400/10 rounded-full blur-3xl"></div>
            <p class="text-indigo-200 text-sm mb-1">Research Institution · {{ auth()->user()->organization ?? '' }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-indigo-100 text-sm mt-2">Anonymised agricultural data, disease trends, and AI diagnostic records for research.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('diagnostics.scan') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">AI Scan</a>
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Wallet</a>
                <a href="{{ route('profile.edit') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Profile</a>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-indigo-600 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Total AI Scans</p>
                <p class="text-4xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-2">{{ number_format($totalDiagnoses) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Farmers</p>
                <p class="text-4xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ number_format($totalFarmers) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Livestock Records</p>
                <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">{{ number_format($totalAnimals) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">AI Accuracy</p>
                <p class="text-4xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $aiAccuracy }}%</p>
                <p class="text-xs text-slate-400 mt-1">Expert-reviewed cases</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Scan Type Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Scan Type Distribution</h3>
                @php
                    $typeColors = ['plant' => 'bg-green-500', 'animal' => 'bg-blue-500', 'soil' => 'bg-amber-500', 'pest' => 'bg-red-500'];
                    $totalScans = array_sum($scanTypeBreakdown);
                @endphp
                @if(empty($scanTypeBreakdown))
                    <p class="text-slate-400 text-sm text-center py-6">No scan data yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach(['plant'=>'Plant Disease','animal'=>'Livestock','soil'=>'Soil Analysis','pest'=>'Pest Detection'] as $type => $label)
                        @php $cnt = $scanTypeBreakdown[$type] ?? 0; $pct = $totalScans > 0 ? round(($cnt / $totalScans) * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 dark:text-gray-300">{{ $label }}</span>
                                <span class="font-bold text-slate-700 dark:text-gray-300">{{ $cnt }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                            </div>
                            <div class="h-2.5 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $typeColors[$type] ?? 'bg-indigo-500' }} rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Monthly Scan Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Scan Volume</h3>
                @php $maxScan = $monthlyScans->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyScans as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
                        <div class="w-full bg-indigo-500 rounded-t-lg" style="height:{{ max(4, round(($m['count'] / $maxScan) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Disease Frequency --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Top Disease Frequencies</h3>
                @forelse($diseaseFrequency as $disease)
                <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                    <span class="text-sm text-slate-700 dark:text-gray-300 font-medium">{{ $disease->disease_name ?: 'Unclassified' }}</span>
                    <span class="text-xs font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full">{{ $disease->count }} cases</span>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">No disease data yet.</p>
                @endforelse
            </div>

            {{-- Geographic Spread --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Farmer Distribution by State</h3>
                @php $maxSt = $stateHeatmap->max('count') ?: 1; @endphp
                @if($stateHeatmap->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No geographic data yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($stateHeatmap as $row)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-gray-400 w-20 flex-shrink-0">{{ $row->state ?? 'Unknown' }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width:{{ round(($row->count / $maxSt) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 w-6 text-right">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Diagnostic Records --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Diagnostic Records (Anonymised)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase text-left">
                        <th class="pb-3 pr-4">Disease / Condition</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Date</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-gray-700">
                        @forelse($recentDiagnoses as $diag)
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                            <td class="py-3 pr-4 font-medium text-slate-800 dark:text-gray-200">{{ $diag->disease_name ?: ($diag->case_type ?? 'Scan') }}</td>
                            <td class="py-3 pr-4"><span class="capitalize text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">{{ $diag->type ?? 'N/A' }}</span></td>
                            <td class="py-3 pr-4"><span class="text-xs {{ $diag->status === 'reviewed' ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400' }}">{{ ucfirst($diag->status ?? 'pending') }}</span></td>
                            <td class="py-3 text-slate-400 text-xs">{{ $diag->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-400">No records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
