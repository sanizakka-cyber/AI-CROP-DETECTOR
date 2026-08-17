<x-app-layout>
    <x-slot name="header">Government Agency Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-green-900 to-emerald-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/5 rounded-full blur-3xl"></div>
            <p class="text-green-200 text-sm mb-1">Government Agency Portal · {{ auth()->user()->organization ?? auth()->user()->state ?? '' }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-green-100 text-sm mt-2">National agricultural surveillance: farmer registry, disease tracking, livestock census, and market data.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('diagnostics.scan') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">AI Scan</a>
                <a href="{{ route('marketplace') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">Marketplace</a>
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Wallet</a>
                <a href="{{ route('profile.edit') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Profile</a>
            </div>
        </div>

        {{-- National KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-green-700 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Registered Farmers</p>
                <p class="text-4xl font-extrabold text-green-700 dark:text-green-400 mt-2">{{ number_format($totalFarmers) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ number_format($verifiedFarmers) }} verified ({{ $totalFarmers > 0 ? round(($verifiedFarmers/$totalFarmers)*100) : 0 }}%)</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-600 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Livestock Records</p>
                <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">{{ number_format($totalAnimals) }}</p>
                <p class="text-xs text-slate-400 mt-1">Animals tracked nationally</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-red-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Disease Scans</p>
                <p class="text-4xl font-extrabold text-red-600 dark:text-red-400 mt-2">{{ number_format($totalDiagnoses) }}</p>
                <p class="text-xs text-slate-400 mt-1">AI diagnoses completed</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Expert Consultations</p>
                <p class="text-4xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ number_format($totalConsults) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $resolutionRate }}% resolved</p>
            </div>
        </div>

        {{-- Secondary KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs text-slate-400 uppercase font-medium mb-1">Agro-Dealers</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalAgroDealers) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs text-slate-400 uppercase font-medium mb-1">Veterinarians</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalVets) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs text-slate-400 uppercase font-medium mb-1">Agronomists</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalAgronomists) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs text-slate-400 uppercase font-medium mb-1">Market GMV</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">₦{{ number_format($marketGMV/1000,0) }}k</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Monthly Farmer Registrations --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Farmer Registrations</h3>
                @php $maxF = $monthlyFarmers->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyFarmers as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-green-700 dark:text-green-400">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
                        <div class="w-full bg-green-600 rounded-t-lg" style="height:{{ max(4, round(($m['count'] / $maxF) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Monthly AI Diagnoses --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Disease Scans</h3>
                @php $maxD = $monthlyDiagnoses->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyDiagnoses as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-red-600 dark:text-red-400">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
                        <div class="w-full bg-red-500 rounded-t-lg" style="height:{{ max(4, round(($m['count'] / $maxD) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Farmer Distribution by State --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Farmer Distribution by State</h3>
                @php $maxSt = $stateBreakdown->max('count') ?: 1; @endphp
                @if($stateBreakdown->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No state data yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($stateBreakdown as $row)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-gray-400 w-24 flex-shrink-0">{{ $row->state ?? 'Unknown' }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-green-600 rounded-full" style="width:{{ round(($row->count / $maxSt) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-green-700 dark:text-green-400 w-8 text-right">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Disease Frequency --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Disease Outbreak Frequency</h3>
                @forelse($diseaseFrequency as $disease)
                <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                    <span class="text-sm text-slate-700 dark:text-gray-300 font-medium">{{ $disease->disease_name }}</span>
                    <span class="text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-0.5 rounded-full">{{ $disease->count }} cases</span>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">No disease data yet.</p>
                @endforelse
            </div>

            {{-- LGA Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Top LGAs by Farmer Count</h3>
                @php $maxLga = $lgaBreakdown->max('count') ?: 1; @endphp
                @if($lgaBreakdown->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No LGA data yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($lgaBreakdown as $row)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-gray-400 w-24 flex-shrink-0 truncate">{{ $row->lga ?? 'Unknown' }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width:{{ round(($row->count / $maxLga) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 w-6 text-right">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Disease Alerts --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Disease Alerts</h3>
                @forelse($diseaseAlerts as $alert)
                <div class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900/30 mb-2 last:mb-0">
                    <span class="text-red-500 font-extrabold text-sm mt-0.5 flex-shrink-0">!</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 dark:text-gray-200 truncate">{{ $alert->disease_name ?? $alert->case_type ?? 'Unknown Condition' }}</p>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $alert->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">No recent alerts.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Farmer Registrations --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Farmer Registrations</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase text-left">
                        <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">State</th><th class="pb-3 pr-4">LGA</th><th class="pb-3 pr-4">Phone</th><th class="pb-3">Joined</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-gray-700">
                        @forelse($recentFarmers as $farmer)
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                            <td class="py-3 pr-4 font-medium text-slate-800 dark:text-gray-200">{{ $farmer->first_name }} {{ $farmer->last_name }}</td>
                            <td class="py-3 pr-4 text-slate-500 dark:text-gray-400">{{ $farmer->state ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-slate-500 dark:text-gray-400">{{ $farmer->lga ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-slate-500 dark:text-gray-400 text-xs">{{ $farmer->phone ?? 'N/A' }}</td>
                            <td class="py-3 text-slate-400 text-xs">{{ $farmer->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">No farmers registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
