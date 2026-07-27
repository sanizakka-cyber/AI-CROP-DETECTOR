<x-app-layout>
    <x-slot name="header">Investor Dashboard</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        {{-- Hero --}}
        <div class="bg-gradient-to-r from-yellow-700 to-amber-500 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
            <div class="absolute right-0 top-0 w-56 h-56 bg-white/10 rounded-full blur-3xl"></div>
            <p class="text-yellow-100 text-sm mb-1">Investor Portal · {{ auth()->user()->organization ?? '' }}</p>
            <h1 class="text-2xl font-extrabold">{{ auth()->user()->name ?: auth()->user()->email }}</h1>
            <p class="text-yellow-100 text-sm mt-2">Platform growth, transaction volume, and agricultural market metrics.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('marketplace') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">Browse Marketplace</a>
                <a href="{{ route('wallet.show') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold rounded-xl transition">My Wallet</a>
            </div>
        </div>

        {{-- Platform KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-emerald-600 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Registered Users</p>
                <p class="text-3xl font-extrabold text-emerald-700 dark:text-emerald-400 mt-2">{{ number_format($totalUsers) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $activeUsers30d }} active (30d)</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-amber-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Platform GMV</p>
                <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">₦{{ number_format($marketGMV, 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Marketplace volume</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-blue-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">Total Revenue</p>
                <p class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">₦{{ number_format($totalRevenue, 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $totalTransacts }} transactions</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-l-4 border-l-purple-500 border-slate-100 dark:border-gray-700">
                <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase">User Retention</p>
                <p class="text-4xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ $retentionRate }}%</p>
                <p class="text-xs text-slate-400 mt-1">Active in last 30 days</p>
            </div>
        </div>

        {{-- Secondary KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs font-medium text-slate-400 uppercase mb-1">Farmers</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalFarmers) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs font-medium text-slate-400 uppercase mb-1">Market Products</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($marketProducts) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs font-medium text-slate-400 uppercase mb-1">AI Scans</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalScans) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-gray-700">
                <p class="text-xs font-medium text-slate-400 uppercase mb-1">Consultations</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalConsults) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- GMV Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Marketplace GMV (₦)</h3>
                @php $maxGmv = $monthlyGMV->max('amount') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyGMV as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ $m['amount'] > 0 ? '₦' . number_format($m['amount']/1000, 0) . 'k' : '' }}</span>
                        <div class="w-full bg-amber-400 rounded-t-lg" style="height:{{ max(4, round(($m['amount'] / $maxGmv) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- User Growth --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly User Growth</h3>
                @php $maxGrowth = $userGrowth->max('count') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($userGrowth as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
                        <div class="w-full bg-emerald-500 rounded-t-lg" style="height:{{ max(4, round(($m['count'] / $maxGrowth) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Revenue Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Monthly Revenue (₦)</h3>
                @php $maxRev = $monthlyRevenue->max('amount') ?: 1; @endphp
                <div class="flex items-end gap-2 h-32 mt-2">
                    @foreach($monthlyRevenue as $m)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">{{ $m['amount'] > 0 ? '₦' . number_format($m['amount']/1000, 0) . 'k' : '' }}</span>
                        <div class="w-full bg-blue-500 rounded-t-lg" style="height:{{ max(4, round(($m['amount'] / $maxRev) * 96)) }}px"></div>
                        <p class="text-xs text-slate-500 dark:text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Platform Health --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-100 dark:border-gray-700 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4 border-b border-slate-100 dark:border-gray-700 pb-3">Platform Health Metrics</h3>
                <div class="space-y-4">
                    @php
                        $metrics = [
                            ['label' => 'User Retention (30d)',    'val' => $retentionRate,                'color' => 'bg-emerald-500', 'suffix' => '%'],
                            ['label' => 'AI Scans / User',         'val' => $totalUsers > 0 ? round($totalScans / $totalUsers, 1) : 0, 'color' => 'bg-purple-500', 'suffix' => ''],
                            ['label' => 'Market Active Products',  'val' => $marketProducts,               'color' => 'bg-amber-500',   'suffix' => ''],
                        ];
                    @endphp
                    @foreach($metrics as $m)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600 dark:text-gray-400">{{ $m['label'] }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $m['val'] }}{{ $m['suffix'] }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full {{ $m['color'] }} rounded-full" style="width:{{ min(100, is_numeric($m['val']) ? $m['val'] : 50) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
