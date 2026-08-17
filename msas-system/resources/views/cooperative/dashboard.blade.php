<x-app-layout>
    <x-slot name="header">Cooperative Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-emerald-900 to-[#0F6B3E] rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-emerald-400/10 rounded-full blur-3xl"></div>
            <p class="text-emerald-200 text-sm mb-1">Cooperative Portal · {{ auth()->user()->organization ?? auth()->user()->state }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-emerald-100 text-sm mt-2">Monitor member farmers, coordinate services, and track agricultural activities in {{ auth()->user()->state ?? 'your region' }}.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">Browse Marketplace</a>
                <a href="{{ route('diagnostics.scan') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">AI Scan</a>
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">Wallet: ₦{{ number_format($walletBalance, 0) }}</a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-600 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Farmers in {{ auth()->user()->state ?? 'State' }}</p>
                <p class="text-4xl font-extrabold text-emerald-700 dark:text-emerald-400 mt-2">{{ number_format($totalFarmers) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Platform Consultations</p>
                <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">{{ number_format($totalConsults) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $resolutionRate }}% resolved</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-purple-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">AI Diagnoses</p>
                <p class="text-4xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ number_format($totalDiagnoses) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Agro-Dealers Nearby</p>
                <p class="text-4xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $agroDealsInState }}</p>
                <p class="text-xs text-slate-400 mt-1">Suppliers in {{ auth()->user()->state ?? 'your state' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- LGA Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Farmers by LGA</h3>
                @if($lgaBreakdown->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No LGA data available yet.</p>
                @else
                    @php $maxLga = $lgaBreakdown->max('count') ?: 1; @endphp
                    <div class="space-y-3">
                        @foreach($lgaBreakdown as $lga)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 dark:text-gray-300 font-medium">{{ $lga->lga }}</span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ $lga->count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width:{{ round(($lga->count / $maxLga) * 100) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Monthly Farmer Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Farmer Registrations</h3>
                @php $maxMonth = $monthlyFarmers->max('count') ?: 1; @endphp
                <div class="flex items-end gap-3 h-32 mt-2">
                    @foreach($monthlyFarmers as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-emerald-500 rounded-t-lg" style="height:{{ max(6, round(($m['count'] / $maxMonth) * 112)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $m['count'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Disease Alerts --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Disease Detections</h3>
                @forelse($diseaseAlerts as $alert)
                <div class="flex items-center justify-between py-2.5 border-b border-slate-50 dark:border-gray-700 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ $alert->disease_name }}</p>
                        <p class="text-xs text-slate-400">{{ $alert->created_at->format('M d, Y') }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">Alert</span>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">No disease detections recorded.</p>
                @endforelse
            </div>

            {{-- Member Farmers --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Member Farmers</h3>
                <div class="space-y-3">
                    @forelse($recentFarmers as $farmer)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr($farmer->first_name ?? 'F', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-gray-200 truncate">{{ $farmer->first_name }} {{ $farmer->last_name }}</p>
                            <p class="text-xs text-slate-400">{{ $farmer->lga ?? 'N/A' }} · {{ $farmer->phone ?? '' }}</p>
                        </div>
                        <span class="text-xs font-bold {{ $farmer->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-400' }}">
                            {{ $farmer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-6">No farmers found in {{ auth()->user()->state ?? 'your state' }}.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
