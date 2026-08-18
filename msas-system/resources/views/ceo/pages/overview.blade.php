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
    .ov-sub-label { font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:14px 0 6px; }
    .ov-sub-label:first-child { margin-top:0; }
    .ov-bar-row { margin-bottom:7px; }
    .ov-bar-row .row-top { display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px; }
    .ov-bar-track { height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden; }
    .ov-bar-fill { height:100%;border-radius:3px; }
    .ov-table { width:100%; font-size:11.5px; border-collapse:collapse; }
    .ov-table th { text-align:left; padding:0 8px 6px 0; font-size:9px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
    .ov-table td { padding:6px 8px 6px 0; border-top:1px solid #f8fafc; white-space:nowrap; }
    .ov-empty { text-align:center; padding:16px; color:#94a3b8; font-size:12px; }
    .ov-pill { font-size:9px; font-weight:700; padding:2px 8px; border-radius:99px; display:inline-flex; white-space:nowrap; }
    .ov-grid-2 { display:grid; grid-template-columns:1fr; gap:16px; }
    @media (min-width:1024px) { .ov-grid-2 { grid-template-columns:1fr 1fr; } }
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

    <div class="ov-grid-2">

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 1 — RISK CENTER
    ═══════════════════════════════════════════════════════════ --}}
    <div id="risk-center" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Risk Center</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v" style="color:#dc2626;">{{ count($diseaseAlerts) }}</div><div class="l">Active Disease Alerts</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $failedPaymentsToday>0?'#dc2626':'#16a34a' }};">{{ $failedPaymentsToday }}</div><div class="l">Failed Payments Today</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $pendingExperts>3?'#d97706':'#16a34a' }};">{{ $pendingExperts }}</div><div class="l">Expert Approvals Pending</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $pendingVerifications>0?'#d97706':'#16a34a' }};">{{ $pendingVerifications }}</div><div class="l">Verifications Pending</div></div>
            </div>

            <div class="ov-sub-label">Recent Risk Activity — Disease Alerts (30 Days)</div>
            @forelse($diseaseAlerts as $alert)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:5px 0;border-top:1px solid #f8fafc;">
                <span style="color:#374151;font-weight:600;">{{ $alert['disease'] }} <span style="color:#94a3b8;font-weight:400;">({{ ucfirst($alert['type']) }})</span></span>
                <span class="ov-pill" style="background:{{ $alert['severity']==='high'?'#fef2f2':($alert['severity']==='medium'?'#fffbeb':'#f0fdf4') }};color:{{ $alert['severity']==='high'?'#dc2626':($alert['severity']==='medium'?'#d97706':'#16a34a') }};">{{ $alert['cases'] }} case{{ $alert['cases']==1?'':'s' }}</span>
            </div>
            @empty
            <div class="ov-empty">No data available</div>
            @endforelse

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.risk-center') }}" class="view-module-link">View Full Risk Center →</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 2 — FINANCIAL
    ═══════════════════════════════════════════════════════════ --}}
    <div id="financial" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Financial</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v" style="color:#16a34a;">₦{{ number_format($payRevenue['today']) }}</div><div class="l">Revenue Today</div></div>
                <div class="mini-stat"><div class="v" style="color:#0369a1;">₦{{ number_format($payRevenue['month']) }}</div><div class="l">Revenue This Month</div></div>
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">₦{{ number_format($mrr) }}</div><div class="l">MRR</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ ($walletStats['pending_withdrawals']??0)>0?'#d97706':'#16a34a' }};">₦{{ number_format($walletStats['withdrawals_value'] ?? 0) }}</div><div class="l">Pending Withdrawals</div></div>
            </div>

            <div class="ov-sub-label">Recent Transactions</div>
            @if($recentTransactions->isNotEmpty())
            <div style="overflow-x:auto;">
            <table class="ov-table">
                <thead><tr><th>Date</th><th>User</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($recentTransactions as $tx)
                <tr>
                    <td style="color:#64748b;">{{ optional($tx->paid_at ?? $tx->created_at)->format('d M, H:i') }}</td>
                    <td style="font-weight:700;color:#0f172a;">{{ trim(($tx->user->first_name ?? '').' '.($tx->user->last_name ?? '')) ?: '—' }}</td>
                    <td style="color:#374151;">{{ Str::limit($tx->description ?? ucfirst($tx->module), 22) }}</td>
                    <td style="font-weight:700;color:#16a34a;">₦{{ number_format($tx->amount) }}</td>
                    <td><span class="ov-pill" style="background:{{ $tx->status==='success'?'#f0fdf4':($tx->status==='failed'?'#fef2f2':'#fffbeb') }};color:{{ $tx->status==='success'?'#16a34a':($tx->status==='failed'?'#dc2626':'#d97706') }};">{{ ucfirst($tx->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @else
            <div class="ov-empty">No data available</div>
            @endif

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.financial') }}" class="view-module-link">View Full Financial →</a>
            </div>
        </div>
    </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 3 — AI ANALYTICS (full width — most important section)
    ═══════════════════════════════════════════════════════════ --}}
    <div id="ai-analytics" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">AI Analytics</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['total']) }}</div><div class="l">Total Scans</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['today']) }}</div><div class="l">Today</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['week']) }}</div><div class="l">This Week</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($aiSummary['month']) }}</div><div class="l">This Month</div></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Confidence + status --}}
                <div>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <div class="mini-stat"><div class="v" style="color:{{ $aiSummary['avg_confidence']>=65?'#16a34a':'#dc2626' }};">{{ $aiSummary['avg_confidence'] }}%</div><div class="l">Avg Confidence</div></div>
                        <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ number_format($aiSummary['pending_review']) }}</div><div class="l">Pending Review</div></div>
                        <div class="mini-stat"><div class="v" style="color:{{ $aiSummary['failed']>0?'#dc2626':'#16a34a' }};">{{ number_format($aiSummary['failed']) }}</div><div class="l">Failed / Unavailable</div></div>
                    </div>
                    <div class="ov-sub-label">Severity Distribution</div>
                    @php $sevTotal = max(1, $severityDistribution->sum()); $sevColors = ['Critical'=>'#dc2626','Severe'=>'#f97316','Moderate'=>'#f59e0b','Mild'=>'#eab308']; @endphp
                    @forelse($severityDistribution as $sevLabel => $sevCnt)
                    @php $sevPct = round($sevCnt/$sevTotal*100); $sevClr = $sevColors[$sevLabel] ?? '#94a3b8'; @endphp
                    <div class="ov-bar-row">
                        <div class="row-top"><span style="color:#374151;font-weight:600;">{{ $sevLabel }}</span><span style="font-weight:800;color:{{ $sevClr }};">{{ $sevCnt }} ({{ $sevPct }}%)</span></div>
                        <div class="ov-bar-track"><div class="ov-bar-fill" style="width:{{ $sevPct }}%;background:{{ $sevClr }};"></div></div>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>

                {{-- Top diagnoses --}}
                <div>
                    <div class="ov-sub-label">Most Common Diagnoses (30d)</div>
                    @forelse($aiStats['top_diseases'] as $td)
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                        <span style="color:#374151;font-weight:600;">{{ Str::limit($td->disease_name, 24) }}</span>
                        <span style="font-weight:800;color:#0D9488;">{{ $td->cnt }}</span>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>

                {{-- Top states --}}
                <div>
                    <div class="ov-sub-label">Top States by Scan Volume</div>
                    @php $stTotal = max(1, $aiTopStates->sum('cnt')); @endphp
                    @forelse($aiTopStates as $ts)
                    @php $stPct = round($ts->cnt/$stTotal*100); @endphp
                    <div class="ov-bar-row">
                        <div class="row-top"><span style="color:#374151;font-weight:600;">{{ $ts->state }}</span><span style="font-weight:800;color:#0D9488;">{{ $ts->cnt }}</span></div>
                        <div class="ov-bar-track"><div class="ov-bar-fill" style="width:{{ $stPct }}%;background:#0D9488;"></div></div>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
            </div>

            <div class="ov-sub-label">Recent Scan Records</div>
            @if($recentScans->isNotEmpty())
            <div style="overflow-x:auto;">
            <table class="ov-table">
                <thead><tr><th>Scan ID</th><th>User</th><th>State</th><th>LGA</th><th>Crop/Subject</th><th>Diagnosis</th><th>Confidence</th><th>Severity</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($recentScans as $scan)
                <tr>
                    <td style="color:#94a3b8;font-family:monospace;">{{ $scan->scan_ref ?? '#'.$scan->id }}</td>
                    <td style="font-weight:700;color:#0f172a;">{{ trim(($scan->user_first_name ?? '').' '.($scan->user_last_name ?? '')) ?: '—' }}</td>
                    <td style="color:#64748b;">{{ $scan->user_state ?? '—' }}</td>
                    <td style="color:#64748b;">{{ $scan->user_lga ?? '—' }}</td>
                    <td style="color:#374151;">{{ Str::limit($scan->subject_name ?? '—', 18) }}</td>
                    <td style="color:#374151;">{{ Str::limit($scan->disease_name ?? '—', 22) }}</td>
                    <td>@if($scan->confidence_score!==null)<span class="ov-pill" style="background:{{ $scan->confidence_score>=65?'#f0fdf4':'#fef2f2' }};color:{{ $scan->confidence_score>=65?'#16a34a':'#dc2626' }};">{{ number_format($scan->confidence_score,0) }}%</span>@else<span style="color:#cbd5e1;">N/A</span>@endif</td>
                    <td style="color:#64748b;">{{ $scan->severity_level ?? '—' }}</td>
                    <td><span class="ov-pill" style="background:#f8fafc;color:#64748b;">{{ $scan->statusLabel }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @else
            <div class="ov-empty">No data available</div>
            @endif

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.ai-analytics') }}" class="view-module-link">View All Scan Records / Full AI Analytics →</a>
            </div>
        </div>
    </div>

    <div class="ov-grid-2">

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 4 — MARKETPLACE
    ═══════════════════════════════════════════════════════════ --}}
    <div id="marketplace" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Marketplace</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v">{{ number_format($marketItems) }}</div><div class="l">Active Listings</div></div>
                <div class="mini-stat"><div class="v">{{ number_format($orderStats['total']) }}</div><div class="l">Total Orders</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ $orderStats['pending']>10?'#dc2626':'#ea580c' }};">{{ number_format($orderStats['pending']) }}</div><div class="l">Pending Orders</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">₦{{ number_format($orderStats['gmv_month']) }}</div><div class="l">GMV This Month</div></div>
            </div>

            <div class="ov-sub-label">Recent Marketplace Activity</div>
            @forelse($recentOrders as $order)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:5px 0;border-top:1px solid #f8fafc;">
                <span style="color:#374151;">
                    <strong>{{ trim(($order->buyer->first_name ?? '').' '.($order->buyer->last_name ?? '')) ?: 'Guest' }}</strong>
                    — ₦{{ number_format($order->total) }}
                </span>
                <span class="ov-pill" style="background:#f8fafc;color:#64748b;">{{ ucfirst($order->status) }}</span>
            </div>
            @empty
            <div class="ov-empty">No data available</div>
            @endforelse

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.marketplace') }}" class="view-module-link">View Full Marketplace →</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 5 — OPERATIONS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="operations" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Operations</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v" style="color:#d97706;">{{ $logisticsStats['pending_dispatch'] }}</div><div class="l">Pending Dispatch</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ $logisticsStats['riders_available'] }}</div><div class="l">Riders Available</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ $consultStats['pending'] }}</div><div class="l">Consults Pending</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ $consultStats['completed'] }}</div><div class="l">Consults Completed</div></div>
            </div>

            <div class="ov-sub-label">Recent Operational Activity</div>
            @forelse($recentConsultations as $c)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:5px 0;border-top:1px solid #f8fafc;">
                <span style="color:#374151;">
                    <strong>{{ trim(($c->user->first_name ?? '').' '.($c->user->last_name ?? '')) ?: 'User' }}</strong>
                    — {{ ucfirst($c->case_type ?? 'consultation') }}
                </span>
                <span class="ov-pill" style="background:#f8fafc;color:#64748b;">{{ ucfirst(str_replace('_',' ',$c->status)) }}</span>
            </div>
            @empty
            <div class="ov-empty">No data available</div>
            @endforelse

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.operations') }}" class="view-module-link">View Full Operations →</a>
            </div>
        </div>
    </div>

    </div>

    <div class="ov-grid-2">

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 6 — GEOGRAPHIC
    ═══════════════════════════════════════════════════════════ --}}
    @php
    $topUserState = $geoChart->isNotEmpty() ? $geoChart->keys()->first() : null;
    $topScanState = $geoChart->isNotEmpty() ? $geoChart->sortByDesc(fn($v) => $v['diagnoses'])->keys()->first() : null;
    @endphp
    <div id="geographic" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Geographic</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $statesCovered }}</div><div class="l">States Covered</div></div>
                <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $lgasCovered }}</div><div class="l">LGAs Covered</div></div>
                <div class="mini-stat"><div class="v" style="font-size:14px;">{{ $topUserState ?? '—' }}</div><div class="l">Top State by Users</div></div>
                <div class="mini-stat"><div class="v" style="font-size:14px;">{{ $topScanState ?? '—' }}</div><div class="l">Top State by Scans</div></div>
            </div>

            <div class="ov-sub-label">Scans by State</div>
            @php $geoTotal = max(1, $geoChart->sum('diagnoses')); @endphp
            @forelse($geoChart->sortByDesc(fn($v) => $v['diagnoses'])->take(6) as $state => $data)
            @php $geoPct = round($data['diagnoses']/$geoTotal*100); @endphp
            <div class="ov-bar-row">
                <div class="row-top"><span style="color:#374151;font-weight:600;">{{ $state }}</span><span style="font-weight:800;color:#7c3aed;">{{ $data['diagnoses'] }} scans · {{ $data['users'] }} users</span></div>
                <div class="ov-bar-track"><div class="ov-bar-fill" style="width:{{ $geoPct }}%;background:#7c3aed;"></div></div>
            </div>
            @empty
            <div class="ov-empty">No data available</div>
            @endforelse

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.geographic') }}" class="view-module-link">View Full Geographic Analytics →</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 7 — USERS & SUBSCRIPTIONS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="users-subscriptions" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">Users &amp; Subscriptions</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 gap-3">
                <div class="mini-stat"><div class="v">{{ number_format($totalUsers) }}</div><div class="l">Total Users</div></div>
                <div class="mini-stat"><div class="v" style="color:#16a34a;">{{ number_format($activeUsers) }}</div><div class="l">Active Users</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ number_format($newUsersWeek) }}</div><div class="l">New This Week</div></div>
                <div class="mini-stat"><div class="v" style="color:#be185d;">{{ number_format($subStats['active']) }}</div><div class="l">Active Subscriptions</div></div>
            </div>

            <div class="grid grid-cols-2 gap-4" style="margin-top:14px;">
                <div>
                    <div class="ov-sub-label">Users by Role</div>
                    @forelse($usersByRole->sortDesc()->take(6) as $role => $count)
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                        <span style="color:#374151;font-weight:600;text-transform:capitalize;">{{ str_replace('-', ' ', $role) }}</span>
                        <span style="font-weight:800;color:#0f172a;">{{ number_format($count) }}</span>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
                <div>
                    <div class="ov-sub-label">Subscription Status</div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;"><span style="color:#374151;font-weight:600;">Trial</span><span style="font-weight:800;color:#2563eb;">{{ number_format($subStats['trial']) }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;"><span style="color:#374151;font-weight:600;">Expired</span><span style="font-weight:800;color:#d97706;">{{ number_format($subStats['expired']) }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;"><span style="color:#374151;font-weight:600;">Cancelled</span><span style="font-weight:800;color:#dc2626;">{{ number_format($subStats['cancelled']) }}</span></div>
                </div>
            </div>

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.users-subs') }}" class="view-module-link">View Full Users &amp; Subscriptions →</a>
            </div>
        </div>
    </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PHASE 8 — SYSTEM (full width)
    ═══════════════════════════════════════════════════════════ --}}
    <div id="system" class="phase-section">
        <div class="phase-head">
            <div class="bi-section-eyebrow" style="margin-bottom:0;">System</div>
        </div>
        <div class="bi-card">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="mini-stat"><div class="v" style="color:{{ $platformHealth>=80?'#16a34a':($platformHealth>=50?'#d97706':'#dc2626') }};">{{ $platformHealth }}%</div><div class="l">Platform Health</div></div>
                <div class="mini-stat"><div class="v" style="color:#2563eb;">{{ $resolutionRate }}%</div><div class="l">Resolution Rate</div></div>
                <div class="mini-stat"><div class="v" style="color:#4f46e5;">{{ $marketItems }}</div><div class="l">Active Listings</div></div>
                <div class="mini-stat"><div class="v" style="color:{{ empty($dashboardErrors) ? '#16a34a' : '#dc2626' }};">{{ empty($dashboardErrors) ? 'OK' : count($dashboardErrors) }}</div><div class="l">{{ empty($dashboardErrors) ? 'No Issues' : 'Data Issues' }}</div></div>
            </div>

            @php
            $healthColors = ['ok'=>'#16a34a','warn'=>'#d97706','error'=>'#dc2626'];
            $healthChecks = [
                'database' => 'Database',
                'ai'       => 'AI Engine',
                'paystack' => 'Payments (Paystack)',
                'queue'    => 'Queue',
                'storage'  => 'Storage',
                'errors'   => 'Error Rate',
            ];
            @endphp
            @if(!empty($systemHealthChecks))
            <div class="ov-sub-label">Live System &amp; API Status</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                @foreach($healthChecks as $key => $label)
                @php $chk = $systemHealthChecks[$key] ?? null; @endphp
                @if($chk)
                <div style="display:flex;align-items:center;gap:7px;font-size:12px;padding:6px 10px;background:#f8fafc;border-radius:8px;">
                    <span style="width:8px;height:8px;border-radius:99px;background:{{ $healthColors[$chk['status']] ?? '#94a3b8' }};flex-shrink:0;"></span>
                    <span style="color:#374151;font-weight:600;">{{ $label }}</span>
                    <span style="color:#94a3b8;margin-left:auto;font-size:10.5px;">{{ $chk['message'] ?? ucfirst($chk['status']) }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
                <div class="ov-sub-label" style="margin-top:0;">Recent Administrative Actions</div>
                @forelse($recentAuditLogs as $log)
                <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px;padding:4px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                    <span style="color:#374151;">
                        <strong>{{ $log->user->first_name ?? 'System' }}</strong>
                        {{ str_replace('_', ' ', $log->action) }}
                    </span>
                    <span style="color:#94a3b8;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                @empty
                <div class="ov-empty">No recent administrative actions</div>
                @endforelse
            </div>

            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('ceo.system') }}" class="view-module-link">View Full System →</a>
            </div>
        </div>
    </div>

    </div>
</x-app-layout>
