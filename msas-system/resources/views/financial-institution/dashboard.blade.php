<x-app-layout>
    <x-slot name="header">Financial Institution Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-blue-400/10 rounded-full blur-3xl"></div>
            <p class="text-blue-200 text-sm mb-1">Financial Institution · {{ auth()->user()->organization ?? '' }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-blue-100 text-sm mt-2">Verified farmer profiles, livestock data, and transaction records for credit assessment.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Wallet</a>
                <a href="{{ route('profile.edit') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Profile</a>
            </div>
        </div>

        {{-- Credit Assessment KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-700 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Total Farmers</p>
                <p class="text-4xl font-extrabold text-blue-700 dark:text-blue-400 mt-2">{{ number_format($totalFarmers) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Active / Verified</p>
                <p class="text-4xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ number_format($verifiedFarmers) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $totalFarmers > 0 ? round(($verifiedFarmers / $totalFarmers) * 100) : 0 }}% of total</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Estimated Asset Base</p>
                <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">₦{{ number_format($totalLivestockValue / 1000000, 1) }}M</p>
                <p class="text-xs text-slate-400 mt-1">{{ number_format($totalAnimals) }} animals</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-purple-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Market Activity (GMV)</p>
                <p class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">₦{{ number_format($marketplaceActivity / 1000, 0) }}k</p>
                <p class="text-xs text-slate-400 mt-1">Platform-wide orders</p>
            </div>
        </div>

        {{-- Creditworthiness Indicators --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
            <h3 class="font-bold text-slate-800 dark:text-white text-base mb-5 border-b border-slate-100 dark:border-gray-700 pb-3">Creditworthiness Indicators</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @php
                    $indicators = [
                        ['label' => 'Farmers with Marketplace Orders', 'val' => $farmersWithOrders, 'total' => $totalFarmers, 'color' => 'bg-blue-500', 'desc' => 'Have purchase/sales history'],
                        ['label' => 'Farmers with AI Health Scans',    'val' => $farmersWithScans,  'total' => $totalFarmers, 'color' => 'bg-indigo-500','desc' => 'Use digital diagnostics'],
                        ['label' => 'Farmers with Expert Consultations','val' => $farmersWithConsults,'total' => $totalFarmers,'color' => 'bg-purple-500','desc' => 'Engaged with extension services'],
                    ];
                @endphp
                @foreach($indicators as $ind)
                @php $pct = $ind['total'] > 0 ? round(($ind['val'] / $ind['total']) * 100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700 dark:text-gray-300 font-medium">{{ $ind['label'] }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($ind['val']) }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-3 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $ind['color'] }} rounded-full transition-all" style="width:{{ $pct }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">{{ $ind['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Monthly New Farmers --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Farmer Registration Pipeline (6 Months)</h3>
                @php $maxM = $monthlyNewFarmers->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyNewFarmers as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
                        <div class="w-full bg-blue-500 rounded-t-lg" style="height:{{ max(4, round(($m['count'] / $maxM) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- State Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Farmers by State</h3>
                @php $maxSt = $stateBreakdown->max('count') ?: 1; @endphp
                @if($stateBreakdown->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-6">No data available.</p>
                @else
                    <div class="space-y-2">
                        @foreach($stateBreakdown as $row)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-600 dark:text-gray-400 w-20 flex-shrink-0">{{ $row->state ?? 'Unknown' }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width:{{ round(($row->count / $maxSt) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-400 w-6 text-right">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent Farmer Profiles --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6 md:col-span-2">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Recent Farmer Profiles</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase text-left">
                            <th class="pb-3 pr-4">Name</th><th class="pb-3 pr-4">State</th><th class="pb-3 pr-4">LGA</th><th class="pb-3 pr-4">Joined</th><th class="pb-3">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-gray-700">
                            @forelse($recentFarmers as $farmer)
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                                <td class="py-3 pr-4 font-medium text-slate-800 dark:text-gray-200">{{ $farmer->first_name }} {{ $farmer->last_name }}</td>
                                <td class="py-3 pr-4 text-slate-500 dark:text-gray-400">{{ $farmer->state ?? 'N/A' }}</td>
                                <td class="py-3 pr-4 text-slate-500 dark:text-gray-400">{{ $farmer->lga ?? 'N/A' }}</td>
                                <td class="py-3 pr-4 text-slate-400 text-xs">{{ $farmer->created_at->format('M Y') }}</td>
                                <td class="py-3"><span class="text-xs font-bold {{ $farmer->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-400' }}">{{ $farmer->is_active ? 'Active' : 'Inactive' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">No farmers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
