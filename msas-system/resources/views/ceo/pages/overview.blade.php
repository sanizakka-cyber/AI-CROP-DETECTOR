<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    @include('ceo.partials.styles')

    <style>
    html { scroll-behavior: smooth; }
    .phase-section { scroll-margin-top: 64px; }
    .view-module-link { font-size:11px; font-weight:700; color:#0F6B3E; text-decoration:none; white-space:nowrap; }
    .view-module-link:hover { text-decoration:underline; }
    .phase-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px; }
    .mini-stat { background:#f8fafc; border-radius:10px; padding:12px; text-align:center; }
    .mini-stat .v { font-size:19px; font-weight:900; color:#0f172a; }
    .mini-stat .l { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-top:3px; }
    </style>

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

    {{-- ═══════════════════════════════════════════════════════════
         WELCOME BANNER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl p-6 text-white" style="background:linear-gradient(135deg,#0B2447 0%,#0e4f2e 55%,#047857 100%);">
        <div class="absolute inset-0" style="background-image:radial-gradient(ellipse at 80% 30%,rgba(255,255,255,0.08) 0%,transparent 65%);pointer-events:none;"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 rounded-full opacity-5" style="background:#fff;transform:translate(30%,40%);"></div>
        <div class="relative z-10">
            <p class="text-emerald-300 text-xs font-bold tracking-widest uppercase mb-1.5">{{ auth()->user()->roleLabel }}</p>
            <h1 class="text-2xl font-extrabold tracking-tight leading-none">
                Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }},
                {{ auth()->user()->displayFirstName }}
            </h1>
            <p class="text-emerald-200/80 text-xs mt-2 mb-1">Executive Overview — Complete MSAS System Summary</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         OVERALL SYSTEM SUMMARY — 10 KPIs
    ═══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="bi-section-eyebrow">Overall System Summary</div>
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

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 1 — RISK CENTER
    ═══════════════════════════════════════════════════════════ --}}
    <div id="risk-center" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 1 &mdash; Risk Center</div>
            <a href="{{ route('ceo.risk-center') }}" class="view-module-link">View Risk Center →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v" style="color:#dc2626;">{{ count($diseaseAlerts) }}</div><div class="l">Disease Alerts (30d)</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $failedPaymentsToday>0?'#dc2626':'#16a34a' }};">{{ $failedPaymentsToday }}</div><div class="l">Failed Payments Today</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $pendingExperts>3?'#d97706':'#16a34a' }};">{{ $pendingExperts }}</div><div class="l">Expert Approvals Pending</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $pendingVerifications>0?'#d97706':'#16a34a' }};">{{ $pendingVerifications }}</div><div class="l">Verifications Pending</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 2 — FINANCIAL
    ═══════════════════════════════════════════════════════════ --}}
    <div id="financial" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 2 &mdash; Financial</div>
            <a href="{{ route('ceo.financial') }}" class="view-module-link">View Financial →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v" style="color:#16a34a;">₦{{ number_format($payRevenue['today']) }}</div><div class="l">Revenue Today</div></div>
                <div class="mini-stat"><div class="v" style="color:#0369a1;">₦{{ number_format($payRevenue['month']) }}</div><div class="l">Revenue This Month</div></div>
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">₦{{ number_format($mrr) }}</div><div class="l">MRR</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ ($walletStats['pending_withdrawals']??0)>0?'#d97706':'#16a34a' }};">₦{{ number_format($walletStats['withdrawals_value'] ?? 0) }}</div><div class="l">Pending Withdrawals</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 3 — AI ANALYTICS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="ai-analytics" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 3 &mdash; AI Analytics</div>
            <a href="{{ route('ceo.ai-analytics') }}" class="view-module-link">View AI Analytics →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['total']) }}</div><div class="l">Total Scans</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['today']) }}</div><div class="l">Today</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['week']) }}</div><div class="l">This Week</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['month']) }}</div><div class="l">This Month</div></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="grid grid-cols-3 gap-3">
                    <div class="mini-stat"><div class="v" style="color:{{ $aiSummary['avg_confidence']>=75?'#16a34a':($aiSummary['avg_confidence']>=50?'#d97706':'#dc2626') }};">{{ $aiSummary['avg_confidence'] }}%</div><div class="l">Avg Confidence</div></div>
                    <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ number_format($aiSummary['pending_review']) }}</div><div class="l">Pending Review</div></div>
                    <div class="mini-stat"><div class="v" style="color:{{ $aiSummary['failed']>0?'#dc2626':'#16a34a' }};">{{ number_format($aiSummary['failed']) }}</div><div class="l">Failed Scans</div></div>
                </div>
                <div>
                    <div style="font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Top States by Scan Volume</div>
                    @forelse($aiTopStates as $ts)
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                        <span style="color:#374151;font-weight:600;">{{ $ts->state }}</span>
                        <span style="font-weight:800;color:#0D9488;">{{ $ts->cnt }}</span>
                    </div>
                    @empty
                    <div style="font-size:12px;color:#94a3b8;">No state data yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 4 — MARKETPLACE
    ═══════════════════════════════════════════════════════════ --}}
    <div id="marketplace" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 4 &mdash; Marketplace</div>
            <a href="{{ route('ceo.marketplace') }}" class="view-module-link">View Marketplace →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v">{{ number_format($orderStats['total']) }}</div><div class="l">Total Orders</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $orderStats['pending']>10?'#dc2626':'#ea580c' }};">{{ number_format($orderStats['pending']) }}</div><div class="l">Pending Orders</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">₦{{ number_format($orderStats['gmv_month']) }}</div><div class="l">GMV This Month</div></div>
                <div class="mini-stat"><div class="v" style="color:#4f46e5;">{{ $topProducts->isNotEmpty() ? Str::limit($topProducts->first()->name, 16) : '—' }}</div><div class="l">Top Product</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 5 — OPERATIONS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="operations" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 5 &mdash; Operations</div>
            <a href="{{ route('ceo.operations') }}" class="view-module-link">View Operations →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v" style="color:#d97706;">{{ $logisticsStats['pending_dispatch'] }}</div><div class="l">Pending Dispatch</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ $logisticsStats['riders_available'] }}</div><div class="l">Riders Available</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ $consultStats['pending'] }}</div><div class="l">Consults Pending</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ $consultStats['completed'] }}</div><div class="l">Consults Completed</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 6 — GEOGRAPHIC
    ═══════════════════════════════════════════════════════════ --}}
    @php
    $topUserState = $geoChart->isNotEmpty() ? $geoChart->keys()->first() : null;
    $topScanState = $geoChart->isNotEmpty() ? $geoChart->sortByDesc(fn($v) => $v['diagnoses'])->keys()->first() : null;
    @endphp
    <div id="geographic" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 6 &mdash; Geographic</div>
            <a href="{{ route('ceo.geographic') }}" class="view-module-link">View Geographic →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $statesCovered }}</div><div class="l">States Covered</div></div>
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $lgasCovered }}</div><div class="l">LGAs Covered</div></div>
                <div class="mini-stat"><div class="v" style="font-size:14px;">{{ $topUserState ?? '—' }}</div><div class="l">Top State by Users</div></div>
                <div class="mini-stat"><div class="v" style="font-size:14px;">{{ $topScanState ?? '—' }}</div><div class="l">Top State by Scans</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 7 — USERS & SUBSCRIPTIONS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="users-subscriptions" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 7 &mdash; Users &amp; Subscriptions</div>
            <a href="{{ route('ceo.users-subs') }}" class="view-module-link">View Users &amp; Subscriptions →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="mini-stat"><div class="v">{{ number_format($totalUsers) }}</div><div class="l">Total Users</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ number_format($activeUsers) }}</div><div class="l">Active Users</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ number_format($newUsersWeek) }}</div><div class="l">New This Week</div></div>
                <div class="mini-stat"><div class="v" style="color:#be185d;">{{ number_format($subStats['active']) }}</div><div class="l">Active Subscriptions</div></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 8 — SYSTEM
    ═══════════════════════════════════════════════════════════ --}}
    <div id="system" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Phase 8 &mdash; System</div>
            <a href="{{ route('ceo.system') }}" class="view-module-link">View System →</a>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="mini-stat"><div class="v" style="color:{{ $platformHealth>=80?'#16a34a':($platformHealth>=50?'#d97706':'#dc2626') }};">{{ $platformHealth }}%</div><div class="l">Platform Health</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ $resolutionRate }}%</div><div class="l">Resolution Rate</div></div>
                <div class="mini-stat"><div class="v" style="color:#4f46e5;">{{ $marketItems }}</div><div class="l">Active Listings</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ empty($dashboardErrors) ? '#16a34a' : '#dc2626' }};">{{ empty($dashboardErrors) ? 'OK' : count($dashboardErrors) }}</div><div class="l">{{ empty($dashboardErrors) ? 'No Issues' : 'Data Issues' }}</div></div>
            </div>
            <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
                <div style="font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Recent Administrative Actions</div>
                @forelse($recentAuditLogs as $log)
                <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px;padding:4px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                    <span style="color:#374151;">
                        <strong>{{ $log->user->first_name ?? 'System' }}</strong>
                        {{ str_replace('_', ' ', $log->action) }}
                    </span>
                    <span style="color:#94a3b8;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                @empty
                <div style="font-size:12px;color:#94a3b8;">No recent administrative actions</div>
                @endforelse
            </div>
        </div>
    </div>

    </div>
</x-app-layout>
