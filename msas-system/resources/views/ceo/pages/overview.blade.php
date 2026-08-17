<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    {{-- ═══════════════════════════════════════════════════════════
         WELCOME BANNER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl p-6 text-white" style="background:linear-gradient(135deg,#0B2447 0%,#0e4f2e 55%,#047857 100%);">
        <div class="absolute inset-0" style="background-image:radial-gradient(ellipse at 80% 30%,rgba(255,255,255,0.08) 0%,transparent 65%);pointer-events:none;"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 rounded-full opacity-5" style="background:#fff;transform:translate(30%,40%);"></div>
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-5">
            <div>
                <p class="text-emerald-300 text-xs font-bold tracking-widest uppercase mb-1.5">{{ auth()->user()->roleLabel }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight leading-none">
                    Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }},
                    {{ auth()->user()->displayFirstName }}
                </h1>
                <p class="text-emerald-200/80 text-xs mt-2">{{ now()->format('l, d F Y') }} &mdash; Platform Intelligence Overview</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                @php
                $bannerKpis = [
                    ['Platform Health',  $platformHealth.'%',            'health'],
                    ['Total Users',      number_format($totalUsers),     'users'],
                    ['Revenue Today',    '₦'.number_format($payRevenue['today']), 'revenue'],
                    ['AI Scans Today',   number_format($aiStats['today']), 'ai'],
                    ['Active Subs',      number_format($subStats['active']), 'subs'],
                ];
                @endphp
                @foreach($bannerKpis as [$bk, $bv, $bi])
                <div class="text-center px-4 py-2.5 rounded-xl min-w-[80px]" style="background:rgba(0,0,0,0.22);backdrop-filter:blur(8px);">
                    <div class="text-lg font-black leading-none">{{ $bv }}</div>
                    <div class="text-[9px] text-emerald-200 font-semibold mt-1 uppercase tracking-wider">{{ $bk }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         EXECUTIVE PULSE — 10 KPIs
    ═══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="bi-section-eyebrow">Executive Pulse — Key Performance Indicators</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @php
        $pulse = [
            ['Total Users',      number_format($totalUsers),                      number_format($activeUsers).' active',                         '#0F6B3E', $revenueGrowth],
            ['New Today',        number_format($newUsersToday),                   number_format($newUsersWeek).' this week',                      '#2563eb', null],
            ['Revenue Today',    '₦'.number_format($payRevenue['today']),         '₦'.number_format($payRevenue['month']).' this month',          '#16a34a', $revenueGrowth],
            ['MRR',              '₦'.number_format($mrr),                         '₦'.number_format($arr).' ARR',                                '#7c3aed', null],
            ['Net Profit',       '₦'.number_format($netProfit),                   ($revenueGrowth>=0?'+':'-').abs($revenueGrowth).'% vs last mo','#0369a1', $revenueGrowth],
            ['AI Scans Today',   number_format($aiStats['today']),                number_format($aiStats['total']).' total',                      '#0D9488', null],
            ['Avg Confidence',   $aiStats['avg_conf'].'%',                        'across all AI diagnoses',                                     $aiStats['avg_conf']>=75?'#16a34a':($aiStats['avg_conf']>=50?'#d97706':'#dc2626'), null],
            ['Active Subs',      number_format($subStats['active']),              number_format($subStats['trial']).' on trial',                  '#be185d', $subStats['growth_pct']],
            ['Orders Pending',   number_format($orderStats['pending']),           number_format($orderStats['total']).' total orders',            $orderStats['pending']>10?'#dc2626':'#ea580c', null],
            ['Verify Rate',      $verifyRate.'%',                                 number_format($verifiedUsers).' verified users',                $verifyRate>=80?'#16a34a':($verifyRate>=50?'#d97706':'#dc2626'), null],
        ];
        @endphp
        @foreach($pulse as [$pl, $pv, $ps, $pc, $pt])
        <div class="bi-card" style="border-left:3px solid {{ $pc }};">
            <div class="bi-eyebrow" style="color:{{ $pc }};">{{ $pl }}</div>
            <div class="bi-num mt-1" style="color:#0f172a;font-size:22px;">{{ $pv }}</div>
            <div class="bi-sub">{{ $ps }}</div>
            @if($pt !== null)
            <div class="mt-1.5 flex items-center gap-0.5 {{ $pt >= 0 ? 'trend-u' : 'trend-d' }}">@if($pt >= 0)<svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l8 16H4L12 4z"/></svg>@else<svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20L4 4h16L12 20z"/></svg>@endif {{ abs($pt) }}%</div>
            @endif
        </div>
        @endforeach
        </div>
    </div>

    </div>
</x-app-layout>
