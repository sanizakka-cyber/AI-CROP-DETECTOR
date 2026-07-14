<x-app-layout>
    <x-slot name="header">System Reports</x-slot>

    <div class="space-y-6">

        <div class="bg-gradient-to-r from-slate-900 to-[#0F6B3E] rounded-2xl p-6 text-white flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-emerald-200 text-sm mb-1">Administration</p>
                <h1 class="text-2xl font-extrabold">System Reports</h1>
                <p class="text-emerald-100 text-sm mt-1">Platform-wide analytics and data exports.</p>
            </div>
            <a href="{{ route('ceo.reports') }}" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold transition">&#9654; CEO Reports</a>
        </div>

        {{-- KPI Summary --}}
        @php
            $totalUsers    = \App\Models\User::count();
            $farmers       = \App\Models\User::where('role','farmer')->count();
            $totalAnimals  = \App\Models\Animal::count();
            $consultations = \App\Models\Consultation::count();
            $resolved      = \App\Models\Consultation::where('status','resolved')->count();
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach([
                ['Total Users', $totalUsers, 'border-l-[#0F6B3E]', 'text-[#0F6B3E]'],
                ['Farmers', $farmers, 'border-l-emerald-500', 'text-emerald-600'],
                ['Animals', $totalAnimals, 'border-l-blue-500', 'text-blue-600'],
                ['Consultations', $consultations, 'border-l-amber-500', 'text-amber-600'],
                ['Resolved', $resolved, 'border-l-green-500', 'text-green-600'],
            ] as [$label, $val, $border, $color])
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-l-4 {{ $border }}">
                <p class="text-xs font-bold text-slate-500 uppercase">{{ $label }}</p>
                <p class="text-3xl font-extrabold {{ $color }} mt-1">{{ number_format($val) }}</p>
            </div>
            @endforeach
        </div>

        {{-- Report Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
                $reportTypes = [
                    ['type'=>'users',       'label'=>'User Activity Report',       'desc'=>'All registered users, roles, and activity status',        'color'=>'bg-emerald-50 border-emerald-200',  'icon_color'=>'text-emerald-600', 'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['type'=>'farmers',     'label'=>'Farmer Registration Report', 'desc'=>'All registered farmers with state and contact details',    'color'=>'bg-blue-50 border-blue-200',        'icon_color'=>'text-blue-600',    'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['type'=>'financial',   'label'=>'Financial Summary Report',   'desc'=>'Income, expenses, and net financial position',             'color'=>'bg-amber-50 border-amber-200',      'icon_color'=>'text-amber-600',   'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['type'=>'livestock',   'label'=>'Livestock Report',           'desc'=>'All registered animals by species, breed, and owner',      'color'=>'bg-green-50 border-green-200',      'icon_color'=>'text-green-600',   'icon'=>'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                    ['type'=>'diseases',    'label'=>'Disease Incidence Report',   'desc'=>'All consultations, disease alerts, and interventions',     'color'=>'bg-red-50 border-red-200',          'icon_color'=>'text-red-600',     'icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    ['type'=>'geographic',  'label'=>'Geographic Distribution',    'desc'=>'User distribution by state and region',                   'color'=>'bg-indigo-50 border-indigo-200',    'icon_color'=>'text-indigo-600',  'icon'=>'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                ];
            @endphp
            @foreach($reportTypes as $r)
            <div class="bg-white rounded-2xl shadow-sm border {{ $r['color'] }} p-6 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 {{ str_replace('border-', 'bg-', explode(' ', $r['color'])[0]) }}/20 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $r['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">{{ $r['label'] }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $r['desc'] }}</p>
                    </div>
                </div>
                <a href="{{ route('ceo.reports.generate', $r['type']) }}"
                    class="mt-auto w-full py-2 text-center text-sm font-semibold text-white bg-[#0F6B3E] hover:bg-[#047857] rounded-xl transition">
                    Generate Report
                </a>
            </div>
            @endforeach
        </div>

        {{-- Users By Role Breakdown --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-4 border-b pb-3">Users by Role</h3>
            @php
                $byRole = \App\Models\User::select('role', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'))
                    ->groupBy('role')->orderByDesc('cnt')->get();
                $maxCnt = $byRole->max('cnt') ?: 1;
            @endphp
            <div class="space-y-2.5">
                @foreach($byRole as $r)
                @php $pct = round(($r->cnt / $maxCnt) * 100); @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-semibold text-slate-700 capitalize">{{ str_replace(['-','_'],' ', $r->role) }}</span>
                        <span class="font-bold text-slate-600">{{ $r->cnt }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 bg-[#0F6B3E] rounded-full" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
