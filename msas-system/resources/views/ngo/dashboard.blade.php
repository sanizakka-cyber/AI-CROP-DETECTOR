<x-app-layout>
    <x-slot name="header">NGO Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-orange-700 to-amber-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/10 rounded-full blur-3xl"></div>
            <p class="text-orange-100 text-sm mb-1">NGO / Development Partner · {{ auth()->user()->organization ?? '' }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-orange-100 text-sm mt-2">Track beneficiary farmers, monitor agricultural outcomes, and measure program impact.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">Marketplace</a>
                <a href="{{ route('diagnostics.scan') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">AI Scan</a>
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Wallet</a>
            </div>
        </div>

        {{-- Impact KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-orange-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Beneficiary Farmers</p>
                <p class="text-4xl font-extrabold text-orange-600 dark:text-orange-400 mt-2">{{ number_format($totalBeneficiaries) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Expert Consultations</p>
                <p class="text-4xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ number_format($totalConsults) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $resolutionRate }}% resolved</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-purple-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">AI Scans Completed</p>
                <p class="text-4xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ number_format($totalDiagnoses) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">AI Adoption Rate</p>
                <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">{{ $adoptionRate }}%</p>
                <p class="text-xs text-slate-400 mt-1">Farmers using AI scans</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Geographic Coverage --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Geographic Reach (Farmers by State)</h3>
                @php $maxState = $stateBreakdown->max('count') ?: 1; @endphp
                @if($stateBreakdown->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No geographic data yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($stateBreakdown as $row)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-gray-400 w-20 flex-shrink-0">{{ $row->state ?? 'Unknown' }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-400 rounded-full" style="width:{{ round(($row->count / $maxState) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-orange-600 dark:text-orange-400 w-8 text-right">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Monthly Registrations --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Beneficiary Growth (6 Months)</h3>
                @php $maxMon = $monthlyRegistrations->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyRegistrations as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-orange-400 rounded-t-lg" style="height:{{ max(6, round(($m['count'] / $maxMon) * 112)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                        <p class="text-xs font-bold text-orange-600 dark:text-orange-400">{{ $m['count'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Diseases --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Disease Prevalence (AI Detected)</h3>
                @forelse($diseaseBreakdown as $disease)
                <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-gray-700 last:border-0">
                    <span class="text-sm text-slate-700 dark:text-gray-300 font-medium">{{ $disease->disease_name }}</span>
                    <span class="text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-0.5 rounded-full">{{ $disease->count }} cases</span>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-6">No disease data yet.</p>
                @endforelse
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Farmer Registrations</h3>
                <div class="space-y-3">
                    @forelse($recentActivity as $farmer)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center text-orange-700 dark:text-orange-400 font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr($farmer->first_name ?? 'F', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-gray-200 truncate">{{ $farmer->first_name }} {{ $farmer->last_name }}</p>
                            <p class="text-xs text-slate-400">{{ $farmer->state ?? 'N/A' }} · {{ $farmer->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-400 text-sm text-center py-6">No recent activity.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
