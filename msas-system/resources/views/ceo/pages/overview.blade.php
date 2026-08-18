<x-app-layout>
    <x-slot name="header">
        @include('ceo.partials.header')
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    @include('ceo.partials.styles')

    @php
    $planColors = [
        'basic'           => ['#16a34a', 'Basic'],
        'basic_pro'       => ['#0D9488', 'Basic Pro'],
        'premium'         => ['#2563eb', 'Premium'],
        'enterprise'      => ['#7c3aed', 'Enterprise'],
        'enterprise_plus' => ['#0B2447', 'Ent. Plus'],
        'pro'             => ['#64748b', 'Pro (Legacy)'],
    ];
    @endphp

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
    .ov-table { width:100%; font-size:11.5px; border-collapse:collapse; }
    .ov-table th { text-align:left; padding:0 8px 6px 0; font-size:9px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
    .ov-table td { padding:6px 8px 6px 0; border-top:1px solid #f8fafc; white-space:nowrap; }
    .ov-empty { text-align:center; padding:16px; color:#94a3b8; font-size:12px; }
    .ov-pill { font-size:9px; font-weight:700; padding:2px 8px; border-radius:99px; display:inline-flex; white-space:nowrap; }
    .ov-input { font-size:12px; border:1px solid #e2e8f0; border-radius:8px; padding:7px 10px; width:100%; background:#fff; }
    .ov-label { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; display:block; }
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
         EXECUTIVE KPI SUMMARY — 10 KPIs
    ═══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="bi-section-eyebrow">Executive KPI Summary</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @php
        $pulse = [
            ['Total Users',      number_format($totalUsers),                      number_format($activeUsers).' active',                         '#0F6B3E', $revenueGrowth],
            ['New Today',        number_format($newUsersToday),                   number_format($newUsersWeek).' this week',                      '#2563eb', null],
            ['Revenue Today',    '₦'.number_format($payRevenue['today']),         '₦'.number_format($payRevenue['month']).' this month',          '#16a34a', $revenueGrowth],
            ['MRR',              '₦'.number_format($mrr),                         '₦'.number_format($arr).' ARR',                                '#7c3aed', null],
            ['Net Profit',       '₦'.number_format($netProfit),                   ($revenueGrowth>=0?'+':'-').abs($revenueGrowth).'% vs last mo','#0369a1', $revenueGrowth],
            ['AI Scans Today',   number_format($aiStats['today']),                number_format($aiStats['total']).' total',                      '#0D9488', null],
            ['Avg Confidence',   $aiStats['avg_conf'].'%',                        'across all AI diagnoses',                                     $aiStats['avg_conf']>=65?'#16a34a':'#dc2626', null],
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
         RISK CENTER ANALYTICS — same Risk & Alert Center as the
         dedicated Risk Center page
    ═══════════════════════════════════════════════════════════ --}}
    @php
    $criticals = [];
    $warnings  = [];
    foreach(($diseaseAlerts ?? []) as $da) {
        if ($da['severity'] === 'high')
            $criticals[] = "Disease outbreak: {$da['disease']} — {$da['cases']} cases in 30 days";
        elseif ($da['severity'] === 'medium')
            $warnings[]  = "Disease alert: {$da['disease']} — {$da['cases']} cases";
    }
    if (($failedPaymentsToday ?? 0) > 0)
        $criticals[] = "{$failedPaymentsToday} payment(s) failed today — requires immediate review";
    if ($orderStats['pending'] > 20)
        $criticals[] = "{$orderStats['pending']} orders stuck in pending — dispatch backlog";
    elseif ($orderStats['pending'] > 5)
        $warnings[]  = "{$orderStats['pending']} orders pending dispatch";
    if ($churnRate > 10)
        $criticals[] = "High subscription churn: {$churnRate}% this month";
    elseif ($churnRate > 5)
        $warnings[]  = "Elevated churn rate: {$churnRate}% this month";
    if (($walletStats['pending_withdrawals'] ?? 0) > 0)
        $warnings[]  = "{$walletStats['pending_withdrawals']} pending withdrawal(s) totalling ₦".number_format($walletStats['withdrawals_value'] ?? 0);
    if ($pendingExperts > 10)
        $criticals[] = "{$pendingExperts} expert accounts awaiting approval";
    elseif ($pendingExperts > 3)
        $warnings[]  = "{$pendingExperts} expert(s) pending approval";
    if (($pendingVerifications ?? 0) > $pendingExperts)
        $warnings[]  = (($pendingVerifications ?? 0) - $pendingExperts)." professional account(s) pending verification";
    @endphp
    <div id="risk-center" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Risk Center Analytics</div></div>
        <div class="bi-card" style="padding:16px 20px;">
            <div class="bi-card-title" style="margin-bottom:12px;">
                <span class="bi-dot" style="background:#ef4444;"></span>
                Risk &amp; Alert Center
                <div class="ml-auto flex gap-2 flex-wrap">
                    @if(count($criticals) > 0)<span class="spill" style="background:#fef2f2;color:#dc2626;">{{ count($criticals) }} Critical</span>@endif
                    @if(count($warnings) > 0)<span class="spill" style="background:#fffbeb;color:#b45309;">{{ count($warnings) }} Warning</span>@endif
                    @if(count($criticals) === 0 && count($warnings) === 0)<span class="spill" style="background:#f0fdf4;color:#16a34a;">All Systems Operational</span>@endif
                </div>
            </div>
            @if(count($criticals) > 0 || count($warnings) > 0)
            <div class="space-y-2">
                @foreach($criticals as $c)
                <div class="risk-crit px-4 py-2.5 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span style="font-size:12px;font-weight:600;color:#7f1d1d;">{{ $c }}</span>
                </div>
                @endforeach
                @foreach($warnings as $w)
                <div class="risk-warn px-4 py-2.5 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span style="font-size:12px;font-weight:600;color:#78350f;">{{ $w }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="risk-ok px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span style="font-size:13px;font-weight:600;color:#14532d;">Platform operating normally — no critical alerts at this time.</span>
            </div>
            @endif
            <div style="margin-top:14px;text-align:right;"><a href="{{ route('ceo.risk-center') }}" class="view-module-link">View Full Risk Center →</a></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FINANCIAL ANALYTICS — same Financial Intelligence as the
         dedicated Financial page
    ═══════════════════════════════════════════════════════════ --}}
    <div id="financial" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Financial Analytics</div></div>
        <div class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @php
            $revBands = [
                ['Today',      $payRevenue['today'], '#16a34a','#f0fdf4'],
                ['This Week',  $payRevenue['week'],  '#2563eb','#eff6ff'],
                ['This Month', $payRevenue['month'], '#7c3aed','#f5f3ff'],
                ['This Year',  $payRevenue['year'],  '#0B2447','#eef2ff'],
                ['All Time',   $payRevenue['total'], '#0f172a','#f8fafc'],
            ];
            @endphp
            @foreach($revBands as [$rl,$rv,$rc,$rbg])
            <div class="bi-card text-center" style="background:{{ $rbg }};border-top:3px solid {{ $rc }};padding:14px;">
                <div class="bi-eyebrow">{{ $rl }}</div>
                <div class="bi-num mt-1" style="color:{{ $rc }};font-size:19px;">₦{{ number_format($rv) }}</div>
            </div>
            @endforeach
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
            $finKpis = [
                ['MRR',             '₦'.number_format($mrr),    'Monthly Recurring Revenue', '#0F6B3E','#f0fdf4'],
                ['ARR',             '₦'.number_format($arr),    'Annual Recurring Revenue',  '#2563eb','#eff6ff'],
                ['Churn Rate',      $churnRate.'%',             'Cancellations this month',  $churnRate>5?'#dc2626':'#475569','#fff'],
                ['Conversion Rate', $conversionRate.'%',        'Trial → Active subscribers','#7c3aed','#f5f3ff'],
            ];
            @endphp
            @foreach($finKpis as [$fl,$fv,$fs,$fc,$fbg])
            <div class="bi-card" style="background:{{ $fbg }};">
                <div class="bi-eyebrow" style="color:{{ $fc }};">{{ $fl }}</div>
                <div class="bi-num mt-1" style="color:{{ $fc }};font-size:22px;">{{ $fv }}</div>
                <div class="bi-sub">{{ $fs }}</div>
            </div>
            @endforeach
            </div>

            <div class="bi-card">
                <div class="bi-card-title">
                    <span class="bi-dot" style="background:#0F6B3E;"></span>
                    Revenue Trend
                    <div class="ml-auto flex gap-1">
                    @foreach(['daily'=>'14 Days','weekly'=>'8 Weeks','monthly'=>'12 Months'] as $period => $label)
                    <button onclick="ovSetRevPeriod('{{ $period }}')" id="ov-rev-btn-{{ $period }}"
                        style="font-size:10px;font-weight:700;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;transition:all .15s;background:{{ $period==='monthly'?'#0F6B3E':'#fff' }};color:{{ $period==='monthly'?'#fff':'#64748b' }};">
                        {{ $label }}
                    </button>
                    @endforeach
                    </div>
                </div>
                <div style="height:220px;position:relative;">
                    <canvas id="ov-revenueTimeChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Wallet &amp; Withdrawals</div>
                    <div class="grid grid-cols-3 gap-3">
                        <div style="background:#f5f3ff;border-radius:10px;padding:14px;text-align:center;">
                            <div class="bi-eyebrow" style="color:#7c3aed;">Total Balance</div>
                            <div class="bi-num" style="color:#7c3aed;font-size:17px;">₦{{ number_format($walletStats['total_balance']) }}</div>
                        </div>
                        <div style="background:{{ ($walletStats['pending_withdrawals']??0)>0?'#fffbeb':'#f8fafc' }};border-radius:10px;padding:14px;text-align:center;">
                            <div class="bi-eyebrow" style="color:#d97706;">Pending</div>
                            <div class="bi-num" style="color:{{ ($walletStats['pending_withdrawals']??0)>0?'#d97706':'#475569' }};font-size:17px;">{{ $walletStats['pending_withdrawals'] ?? 0 }}</div>
                            <div class="bi-sub" style="font-size:10px;">withdrawals</div>
                        </div>
                        <div style="background:{{ ($walletStats['withdrawals_value']??0)>0?'#fef2f2':'#f8fafc' }};border-radius:10px;padding:14px;text-align:center;">
                            <div class="bi-eyebrow" style="color:#dc2626;">Value</div>
                            <div class="bi-num" style="color:{{ ($walletStats['withdrawals_value']??0)>0?'#dc2626':'#475569' }};font-size:17px;">₦{{ number_format($walletStats['withdrawals_value'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#be185d;"></span>Subscription Revenue</div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div style="background:#fdf2f8;border-radius:10px;padding:14px;text-align:center;">
                            <div class="bi-eyebrow" style="color:#be185d;">This Month</div>
                            <div class="bi-num" style="color:#be185d;font-size:18px;">₦{{ number_format($subStats['revenue_month']) }}</div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:14px;text-align:center;">
                            <div class="bi-eyebrow">All Time</div>
                            <div class="bi-num" style="color:#0f172a;font-size:18px;">₦{{ number_format($subStats['revenue_total']) }}</div>
                        </div>
                    </div>
                    <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
                        @foreach($planColors as $pk => [$pc, $pn])
                        @php $rev = $subStats['revenue_by_plan'][$pk] ?? 0; @endphp
                        @if($rev > 0)
                        <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px;border-bottom:1px solid #f8fafc;">
                            <span style="color:#374151;font-weight:600;">{{ $pn }}</span>
                            <span style="font-weight:800;color:{{ $pc }};">₦{{ number_format($rev) }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#16a34a;"></span>Recent Transactions</div>
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
            </div>

            <div style="text-align:right;"><a href="{{ route('ceo.financial') }}" class="view-module-link">View Full Financial →</a></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         AI ANALYTICS — same analytics as the dedicated AI Analytics
         page (CeoScanAnalyticsController), including real, working
         filters via the shared HasCeoScanFilters trait
    ═══════════════════════════════════════════════════════════ --}}
    <div id="ai-analytics" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">AI Analytics</div></div>
        <div class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bi-card text-center"><div class="bi-eyebrow">Total Scans</div><div class="bi-num mt-1" style="font-size:20px;">{{ number_format($aiSummary['total']) }}</div></div>
                <div class="bi-card text-center"><div class="bi-eyebrow">Today</div><div class="bi-num mt-1" style="font-size:20px;">{{ number_format($aiSummary['today']) }}</div></div>
                <div class="bi-card text-center"><div class="bi-eyebrow">This Week</div><div class="bi-num mt-1" style="font-size:20px;">{{ number_format($aiSummary['week']) }}</div></div>
                <div class="bi-card text-center"><div class="bi-eyebrow">This Month</div><div class="bi-num mt-1" style="font-size:20px;">{{ number_format($aiSummary['month']) }}</div></div>
            </div>

            {{-- Filters — real, working: submits back to Overview and re-scopes this section only --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>Filters</div>
                <form method="GET" action="{{ route('ceo.overview') }}#ai-analytics" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div>
                        <label class="ov-label">Date Range</label>
                        <select name="range" class="ov-input" onchange="this.form.submit()">
                            @foreach(['30d'=>'Last 30 Days','7d'=>'Last 7 Days','today'=>'Today','yesterday'=>'Yesterday','this_month'=>'This Month','last_month'=>'Last Month','custom'=>'Custom'] as $rv=>$rl)
                            <option value="{{ $rv }}" @selected(request('range','30d')===$rv)>{{ $rl }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(request('range')==='custom')
                    <div><label class="ov-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="ov-input"></div>
                    <div><label class="ov-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="ov-input"></div>
                    @endif
                    <div>
                        <label class="ov-label">State</label>
                        <select name="state" class="ov-input" onchange="var l=this.form.querySelector('select[name=lga]'); if(l) l.value=''; this.form.submit()">
                            <option value="">All States</option>
                            @foreach($ovStates as $st)
                            <option value="{{ $st }}" @selected(request('state')===$st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ov-label">LGA</label>
                        <select name="lga" class="ov-input" @if(!request('state')) disabled @endif>
                            <option value="">All LGAs</option>
                            @foreach($ovLgasForState as $lg)
                            <option value="{{ $lg }}" @selected(request('lga')===$lg)>{{ $lg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="ov-label">Crop / Subject</label><input type="text" name="crop" value="{{ request('crop') }}" placeholder="e.g. Maize" class="ov-input"></div>
                    <div><label class="ov-label">Diagnosis</label><input type="text" name="diagnosis" value="{{ request('diagnosis') }}" placeholder="e.g. Blight" class="ov-input"></div>
                    <div>
                        <label class="ov-label">Confidence</label>
                        <select name="confidence" class="ov-input">
                            <option value="">Any</option>
                            <option value="high"   @selected(request('confidence')==='high')>High (&ge;80%)</option>
                            <option value="medium" @selected(request('confidence')==='medium')>Medium (60&ndash;79%)</option>
                            <option value="low"    @selected(request('confidence')==='low')>Low (&lt;60%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="ov-label">Status</label>
                        <select name="status" class="ov-input">
                            <option value="">Any</option>
                            <option value="completed"      @selected(request('status')==='completed')>Completed</option>
                            <option value="low_confidence" @selected(request('status')==='low_confidence')>Low Confidence</option>
                            <option value="processing"     @selected(request('status')==='processing')>Processing</option>
                            <option value="failed"         @selected(request('status')==='failed')>Failed</option>
                        </select>
                    </div>
                    <div><label class="ov-label">Scan ID</label><input type="text" name="scan_ref" value="{{ request('scan_ref') }}" placeholder="MSAS-SCN-..." class="ov-input"></div>
                    <div class="flex items-end gap-2"><button type="submit" class="text-xs font-semibold text-white bg-[#0F6B3E] hover:bg-[#0B2447] px-4 py-2 rounded-lg transition-colors w-full">Apply Filters</button></div>
                    <div class="flex items-end gap-2"><a href="{{ route('ceo.overview') }}#ai-analytics" class="text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-lg transition-colors w-full text-center">Reset</a></div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#0D9488;"></span>Filtered Results</div>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div class="mini-stat"><div class="v">{{ number_format($ovFilteredCount) }}</div><div class="l">Scans Matching Filters</div></div>
                        <div class="mini-stat"><div class="v" style="color:{{ $ovFilteredAvgConf>=65?'#16a34a':'#dc2626' }};">{{ $ovFilteredAvgConf }}%</div><div class="l">Avg Confidence</div></div>
                    </div>
                    @php $statusColors = ['Completed'=>'#16a34a','Low Confidence'=>'#d97706','Processing'=>'#2563eb','Failed'=>'#dc2626','Unknown'=>'#94a3b8']; $statusTotal = max(1, $ovStatusBreakdown->sum()); @endphp
                    @forelse($ovStatusBreakdown as $label => $cnt)
                    @php $pct = round($cnt/$statusTotal*100); $clr = $statusColors[$label] ?? '#94a3b8'; @endphp
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="color:#374151;font-weight:600;">{{ $label }}</span><span style="font-weight:800;color:{{ $clr }};">{{ $cnt }} ({{ $pct }}%)</span></div>
                        <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:3px;"></div></div>
                    </div>
                    @empty
                    <div class="ov-empty">No scans match these filters</div>
                    @endforelse
                </div>

                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#2563eb;"></span>Scan Trend</div>
                    <div style="height:170px;position:relative;"><canvas id="ov-dailyScanChart"></canvas></div>
                </div>

                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#dc2626;"></span>Severity Distribution</div>
                    @php $sevTotal = max(1, $severityDistribution->sum()); $sevColors = ['Critical'=>'#dc2626','Severe'=>'#f97316','Moderate'=>'#f59e0b','Mild'=>'#eab308']; @endphp
                    @forelse($severityDistribution as $sevLabel => $sevCnt)
                    @php $sevPct = round($sevCnt/$sevTotal*100); $sevClr = $sevColors[$sevLabel] ?? '#94a3b8'; @endphp
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="color:#374151;font-weight:600;">{{ $sevLabel }}</span><span style="font-weight:800;color:{{ $sevClr }};">{{ $sevCnt }} ({{ $sevPct }}%)</span></div>
                        <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $sevPct }}%;background:{{ $sevClr }};border-radius:3px;"></div></div>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#0D9488;"></span>Most Common Diagnoses (30d)</div>
                    @forelse($aiStats['top_diseases'] as $td)
                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-top:1px solid #f8fafc;">
                        <span style="color:#374151;font-weight:600;">{{ Str::limit($td->disease_name, 28) }}</span>
                        <span style="font-weight:800;color:#0D9488;">{{ $td->cnt }}</span>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Top States by Scan Volume</div>
                    @php $stTotal = max(1, $aiTopStates->sum('cnt')); @endphp
                    @forelse($aiTopStates as $ts)
                    @php $stPct = round($ts->cnt/$stTotal*100); @endphp
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="color:#374151;font-weight:600;">{{ $ts->state }}</span><span style="font-weight:800;color:#7c3aed;">{{ $ts->cnt }}</span></div>
                        <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $stPct }}%;background:#7c3aed;border-radius:3px;"></div></div>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
            </div>

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0f172a;"></span>Recent Scan Records</div>
                @if($ovScans->isNotEmpty())
                <div style="overflow-x:auto;">
                <table class="ov-table">
                    <thead><tr><th>Scan ID</th><th>User</th><th>State</th><th>LGA</th><th>Crop/Subject</th><th>Diagnosis</th><th>Confidence</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @foreach($ovScans as $scan)
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
                        <td style="color:#94a3b8;">{{ $scan->created_at->format('d M, H:i') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @else
                <div class="ov-empty">No scans match these filters</div>
                @endif
                <div style="margin-top:12px;text-align:right;"><a href="{{ route('ceo.ai-analytics', request()->query()) }}" class="view-module-link">View All Scan Records / Full AI Analytics →</a></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MARKETPLACE ANALYTICS — same Marketplace Intelligence as the
         dedicated Marketplace page
    ═══════════════════════════════════════════════════════════ --}}
    <div id="marketplace" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Marketplace Analytics</div></div>
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#4f46e5;"></span>Order Pipeline</div>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
            @php
            $orderPills = [
                ['Total',      $orderStats['total'],     '#0f172a','#f8fafc'],
                ['Pending',    $orderStats['pending'],   '#d97706','#fffbeb'],
                ['Processing', $orderStats['processing'],'#2563eb','#eff6ff'],
                ['Shipped',    $orderStats['shipped'],   '#7c3aed','#f5f3ff'],
                ['Delivered',  $orderStats['delivered'], '#16a34a','#f0fdf4'],
                ['Cancelled',  $orderStats['cancelled'], '#dc2626','#fef2f2'],
            ];
            @endphp
            @foreach($orderPills as [$ol,$ov,$oc,$obg])
            <div style="background:{{ $obg }};border-radius:10px;padding:12px 8px;text-align:center;border-top:3px solid {{ $oc }};">
                <div style="font-size:22px;font-weight:900;color:{{ $oc }};">{{ $ov }}</div>
                <div style="font-size:9px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $ol }}</div>
            </div>
            @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="bi-eyebrow" style="color:#4f46e5;margin-bottom:8px;">Gross Merchandise Value</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div style="background:#f0fdf4;border-radius:10px;padding:14px;text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">GMV This Month</div>
                            <div style="font-size:19px;font-weight:900;color:#16a34a;">₦{{ number_format($orderStats['gmv_month']) }}</div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:14px;text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Total GMV</div>
                            <div style="font-size:19px;font-weight:900;color:#0f172a;">₦{{ number_format($orderStats['gmv']) }}</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="bi-eyebrow" style="color:#4f46e5;margin-bottom:8px;">Top Products by Orders</div>
                    @forelse($topProducts as $prod)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:18px;height:18px;border-radius:50%;background:#eff6ff;font-size:9px;font-weight:800;color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $loop->iteration }}</span>
                            <span style="font-size:12px;font-weight:600;color:#374151;">{{ Str::limit($prod->name, 28) }}</span>
                        </div>
                        <span style="font-size:11px;font-weight:800;color:#4f46e5;background:#eff6ff;padding:2px 8px;border-radius:99px;">{{ $prod->order_count }}</span>
                    </div>
                    @empty
                    <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">No orders yet</div>
                    @endforelse
                </div>
            </div>

            <div class="ov-sub-label">Recent Marketplace Activity</div>
            @forelse($recentOrders as $order)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:5px 0;border-top:1px solid #f8fafc;">
                <span style="color:#374151;"><strong>{{ trim(($order->buyer->first_name ?? '').' '.($order->buyer->last_name ?? '')) ?: 'Guest' }}</strong> — ₦{{ number_format($order->total) }}</span>
                <span class="ov-pill" style="background:#f8fafc;color:#64748b;">{{ ucfirst($order->status) }}</span>
            </div>
            @empty
            <div class="ov-empty">No data available</div>
            @endforelse

            <div style="margin-top:12px;text-align:right;"><a href="{{ route('ceo.marketplace') }}" class="view-module-link">View Full Marketplace →</a></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         OPERATIONS ANALYTICS — same Operations Intelligence as the
         dedicated Operations page
    ═══════════════════════════════════════════════════════════ --}}
    <div id="operations" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Operations Analytics</div></div>
        <div class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#ea580c;"></span>Logistics &amp; Delivery</div>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                    @php
                    $logCols = [
                        ['Pending Dispatch', $logisticsStats['pending_dispatch'], '#d97706','#fffbeb'],
                        ['Riders Available', $logisticsStats['riders_available'], '#16a34a','#f0fdf4'],
                        ['In Transit',       $logisticsStats['in_transit'],       '#2563eb','#eff6ff'],
                        ['Delivered Total',  $logisticsStats['delivered'],         '#0f172a','#f8fafc'],
                    ];
                    @endphp
                    @foreach($logCols as [$ll,$lv,$lc,$lbg])
                    <div style="background:{{ $lbg }};border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $ll }}</div>
                        <div style="font-size:22px;font-weight:900;color:{{ $lc }};">{{ $lv }}</div>
                    </div>
                    @endforeach
                    </div>
                    @php $totalRiders = max(1, $logisticsStats['riders_available'] + $logisticsStats['riders_busy']); @endphp
                    <div style="padding-top:10px;border-top:1px solid #f1f5f9;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;"><span style="color:#64748b;">Rider utilisation</span><span style="font-weight:800;color:#ea580c;">{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}% active</span></div>
                        <div style="height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div style="height:100%;background:linear-gradient(90deg,#ea580c,#f97316);border-radius:4px;width:{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}%;"></div></div>
                    </div>
                </div>

                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#2563eb;"></span>Expert Consultations</div>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                    @php
                    $cCols = [
                        ['Pending',      $consultStats['pending'],     '#d97706','#fffbeb'],
                        ['In Progress',  $consultStats['in_progress'], '#2563eb','#eff6ff'],
                        ['Completed',    $consultStats['completed'],   '#16a34a','#f0fdf4'],
                        ['Avg Response', (round($consultStats['avg_hours']??0,1)).'h', '#7c3aed','#f5f3ff'],
                    ];
                    @endphp
                    @foreach($cCols as [$cl,$cv,$cc,$cbg])
                    <div style="background:{{ $cbg }};border-radius:10px;padding:12px;text-align:center;">
                        <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $cl }}</div>
                        <div style="font-size:22px;font-weight:900;color:{{ $cc }};">{{ $cv }}</div>
                    </div>
                    @endforeach
                    </div>
                    <div class="ov-sub-label">Recent Operational Activity</div>
                    @forelse($recentConsultations as $c)
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:4px 0;border-top:1px solid #f8fafc;">
                        <span style="color:#374151;"><strong>{{ trim(($c->user->first_name ?? '').' '.($c->user->last_name ?? '')) ?: 'User' }}</strong> — {{ ucfirst($c->case_type ?? 'consultation') }}</span>
                        <span class="ov-pill" style="background:#f8fafc;color:#64748b;">{{ ucfirst(str_replace('_',' ',$c->status)) }}</span>
                    </div>
                    @empty
                    <div class="ov-empty">No data available</div>
                    @endforelse
                </div>
            </div>

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#475569;"></span>Staff &amp; HR</div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @php
                $hrKpis = [
                    ['Staff Present',    "{$presentToday} / {$staffCount}", '#0f172a','#f8fafc'],
                    ['Pending Leave',    $pendingLeaves,  $pendingLeaves>0?'#d97706':'#16a34a', $pendingLeaves>0?'#fffbeb':'#f0fdf4'],
                    ['Expert Approvals', $pendingExperts, $pendingExperts>0?'#d97706':'#16a34a', $pendingExperts>0?'#fffbeb':'#f0fdf4'],
                    ['Pending Listings', $pendingListings,$pendingListings>0?'#d97706':'#475569',$pendingListings>0?'#fffbeb':'#f8fafc'],
                ];
                @endphp
                @foreach($hrKpis as [$hl,$hv,$hc,$hbg])
                <div style="background:{{ $hbg }};border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">{{ $hl }}</div>
                    <div style="font-size:20px;font-weight:900;color:{{ $hc }};">{{ $hv }}</div>
                </div>
                @endforeach
                </div>
            </div>

            <div style="text-align:right;"><a href="{{ route('ceo.operations') }}" class="view-module-link">View Full Operations →</a></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         GEOGRAPHIC ANALYTICS — same Geographic Intelligence as the
         dedicated Geographic page
    ═══════════════════════════════════════════════════════════ --}}
    <div id="geographic" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Geographic Analytics</div></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Top States — Users &amp; AI Scans</div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $statesCovered }}</div><div class="l">States Covered</div></div>
                    <div class="mini-stat"><div class="v" style="color:#7c3aed;">{{ $lgasCovered }}</div><div class="l">LGAs Covered</div></div>
                </div>
                <div style="height:200px;position:relative;"><canvas id="ov-geoBarChart"></canvas></div>
                @if($geoChart->isEmpty())<p style="font-size:13px;color:#94a3b8;text-align:center;padding:12px;">No state data yet</p>@endif
            </div>
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>State Activity Ranking</div>
                @if(!empty($stateActivity))
                @php $maxState = max($stateActivity ?: [1]); @endphp
                <div class="space-y-2.5">
                @foreach($stateActivity as $state => $cnt)
                @php $sp = $maxState > 0 ? round(($cnt/$maxState)*100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="font-weight:600;color:#374151;">{{ $state }}</span><span style="font-weight:800;color:#7c3aed;">{{ $cnt }}</span></div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $sp }}%;background:linear-gradient(90deg,#7c3aed,#6d28d9);border-radius:3px;"></div></div>
                </div>
                @endforeach
                </div>
                @else
                <div class="ov-empty">No state data yet</div>
                @endif
            </div>
        </div>
        <div style="text-align:right;margin-top:10px;"><a href="{{ route('ceo.geographic') }}" class="view-module-link">View Full Geographic Analytics →</a></div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         USERS & SUBSCRIPTIONS ANALYTICS — same User & Subscription
         Analytics as the dedicated Users & Subs page
    ═══════════════════════════════════════════════════════════ --}}
    <div id="users-subscriptions" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">Users &amp; Subscriptions Analytics</div></div>
        <div class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @php
            $userKpis = [
                ['New Today',     $newUsersToday, '#16a34a','#f0fdf4', ''],
                ['New This Week', $newUsersWeek,  '#2563eb','#eff6ff', ''],
                ['Total Users',   $totalUsers,    '#0f172a','#f8fafc', ''],
                ['Verified',      $verifiedUsers, '#7c3aed','#f5f3ff', ''],
                ['Verify Rate',   $verifyRate,    ($verifyRate>=80?'#16a34a':($verifyRate>=50?'#d97706':'#dc2626')),'#fff','%'],
            ];
            @endphp
            @foreach($userKpis as [$ul,$uv,$uc,$ubg,$usuf])
            <div class="bi-card text-center" style="background:{{ $ubg }};border-top:3px solid {{ $uc }};padding:14px;">
                <div class="bi-eyebrow">{{ $ul }}</div>
                <div class="bi-num mt-1" style="color:{{ $uc }};font-size:22px;">{{ number_format($uv) }}{{ $usuf }}</div>
            </div>
            @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>User Growth (6 Months)</div>
                    <div style="height:200px;position:relative;"><canvas id="ov-userGrowthChart"></canvas></div>
                </div>
                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>Users by Role</div>
                    @php
                    $roleClrs = ['farmer'=>'#0F6B3E','vet'=>'#2563eb','agronomist'=>'#0D9488','admin'=>'#dc2626','agro-dealer'=>'#d97706','extension-officer'=>'#4f46e5','ceo'=>'#7c3aed','finance'=>'#be185d','hr'=>'#ea580c','general-user'=>'#475569'];
                    $roleTotal = max(1, $usersByRole->sum());
                    @endphp
                    <div class="space-y-2.5">
                    @foreach($usersByRole as $role => $cnt)
                    @php $pct = round(($cnt/$roleTotal)*100); $clr = $roleClrs[$role] ?? '#94a3b8'; @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="font-weight:600;color:#374151;text-transform:capitalize;">{{ str_replace('-',' ',$role) }}</span><span style="font-weight:800;color:{{ $clr }};">{{ $cnt }} <span style="color:#94a3b8;font-weight:400;">({{ $pct }}%)</span></span></div>
                        <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:3px;"></div></div>
                    </div>
                    @endforeach
                    </div>
                </div>
            </div>

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#be185d;"></span>Subscription Analytics</div>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
                @php
                $subStatusCols = [
                    ['Total',    $subStats['total'],     '#0f172a'],
                    ['Active',   $subStats['active'],    '#16a34a'],
                    ['Trial',    $subStats['trial'],     '#2563eb'],
                    ['Expired',  $subStats['expired'],   '#dc2626'],
                    ['Cancelled',$subStats['cancelled'], '#94a3b8'],
                    ['Suspended',$subStats['suspended'], '#d97706'],
                ];
                @endphp
                @foreach($subStatusCols as [$sl,$sv,$sc])
                <div style="background:#f8fafc;border-radius:10px;padding:10px 8px;text-align:center;border-left:3px solid {{ $sc }};">
                    <div style="font-size:20px;font-weight:900;color:{{ $sc }};">{{ $sv }}</div>
                    <div style="font-size:9px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:2px;">{{ $sl }}</div>
                </div>
                @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div style="position:relative;width:140px;height:140px;flex-shrink:0;">
                            <canvas id="ov-subPlanDonut"></canvas>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                                <span style="font-size:20px;font-weight:900;color:#0f172a;">{{ array_sum($subStats['by_plan']) ?: $subStats['active'] }}</span>
                                <span style="font-size:10px;color:#94a3b8;">Active</span>
                            </div>
                        </div>
                        <div style="flex:1;min-width:120px;">
                        @foreach($planColors as $pk => [$pc, $pn])
                        @php $cnt2 = $subStats['by_plan'][$pk] ?? 0; @endphp
                        @if($cnt2 > 0)
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;margin-bottom:7px;">
                            <span style="display:flex;align-items:center;gap:6px;color:#374151;font-weight:500;"><span style="width:8px;height:8px;border-radius:50%;background:{{ $pc }};flex-shrink:0;display:inline-block;"></span>{{ $pn }}</span>
                            <span style="font-weight:800;color:{{ $pc }};">{{ $cnt2 }}</span>
                        </div>
                        @endif
                        @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="bi-eyebrow" style="margin-bottom:8px;">Subscribers by Plan</div>
                        @foreach($planColors as $pk => [$pc, $pn])
                        @php $cnt3=$subStats['by_plan'][$pk]??0; $subTot=max(1,$subStats['active']+$subStats['trial']); $pct3=round($cnt3/$subTot*100); @endphp
                        @if($cnt3 > 0 || in_array($pk, ['basic','basic_pro','premium']))
                        <div style="margin-bottom:7px;">
                            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span style="color:#374151;font-weight:600;">{{ $pn }}</span><span style="font-weight:800;color:{{ $pc }};">{{ $cnt3 }}</span></div>
                            <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $pct3 }}%;background:{{ $pc }};border-radius:3px;"></div></div>
                        </div>
                        @endif
                        @endforeach
                        <div style="margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;font-size:12px;">
                            <span style="color:#64748b;">New this month</span>
                            <span style="font-weight:800;color:{{ $subStats['growth_pct']>=0?'#16a34a':'#dc2626' }};">{{ $subStats['new_this_month'] }} ({{ $subStats['growth_pct']>=0?'↑':'↓' }}{{ abs($subStats['growth_pct']) }}%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align:right;"><a href="{{ route('ceo.users-subs') }}" class="view-module-link">View Full Users &amp; Subscriptions →</a></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SYSTEM ANALYTICS — same System Health & Recent Activity as
         the dedicated System page, plus real live health checks
    ═══════════════════════════════════════════════════════════ --}}
    <div id="system" class="phase-section">
        <div class="phase-head"><div class="bi-section-eyebrow" style="margin-bottom:0;">System Analytics</div></div>
        <div class="space-y-4">
            @php
            $healthColors = ['ok'=>'#16a34a','warn'=>'#d97706','error'=>'#dc2626'];
            $healthChecks = ['database'=>'Database','ai'=>'AI Engine','paystack'=>'Payments (Paystack)','queue'=>'Queue','storage'=>'Storage','errors'=>'Error Rate'];
            @endphp
            @if(!empty($systemHealthChecks))
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0f172a;"></span>Live System &amp; API Status</div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($healthChecks as $key => $label)
                    @php $chk = $systemHealthChecks[$key] ?? null; @endphp
                    @if($chk)
                    <div style="display:flex;align-items:center;gap:7px;font-size:12px;padding:8px 10px;background:#f8fafc;border-radius:8px;">
                        <span style="width:8px;height:8px;border-radius:99px;background:{{ $healthColors[$chk['status']] ?? '#94a3b8' }};flex-shrink:0;"></span>
                        <span style="color:#374151;font-weight:600;">{{ $label }}</span>
                        <span style="color:#94a3b8;margin-left:auto;font-size:10.5px;">{{ $chk['message'] ?? ucfirst($chk['status']) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#475569;"></span>Platform Performance Indicators</div>
                @php
                $gauges = [
                    ['Platform Health',        $platformHealth,            90, '#0F6B3E', false],
                    ['Case Resolution Rate',   $resolutionRate,            85, '#2563eb', false],
                    ['Active User Rate',       $activePct,                 80, '#0D9488', false],
                    ['Expert Approval Pending',min(100,$pendingExperts*10),10, '#d97706', true],
                    ['Market Listings Active', min(100,$marketItems*5),    50, '#4f46e5', false],
                ];
                @endphp
                <div class="space-y-3.5">
                @foreach($gauges as [$gl,$gv,$gt,$gc,$ginv])
                @php $gp=min(100,$gv); $gok=$ginv?$gp<=$gt:$gp>=$gt; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;">
                        <span style="font-weight:600;color:#374151;">{{ $gl }}</span>
                        <span style="font-weight:800;color:{{ $gok?'#16a34a':'#d97706' }};">{{ $gv }}% <span style="font-size:10px;color:#94a3b8;font-weight:400;">/ {{ $gt }}% target</span></span>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div style="height:100%;background:{{ $gc }};border-radius:4px;width:{{ $gp }}%;"></div></div>
                </div>
                @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bi-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                        <div class="bi-card-title" style="margin-bottom:0;"><span class="bi-dot" style="background:#0F6B3E;"></span>Recent Registrations</div>
                        <a href="{{ route('ceo.users') }}" style="font-size:11px;font-weight:700;color:#0F6B3E;text-decoration:none;">View all →</a>
                    </div>
                    <div style="overflow-x:auto;">
                    <table class="ov-table">
                        <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($recentUsers as $u)
                        <tr>
                            <td style="font-weight:700;color:#0f172a;">{{ $u->first_name }} {{ $u->last_name }}</td>
                            <td><span style="background:#f0fdf4;color:#16a34a;border-radius:99px;padding:2px 7px;font-size:10px;font-weight:700;text-transform:capitalize;white-space:nowrap;">{{ str_replace('-',' ',$u->role) }}</span></td>
                            <td style="color:#94a3b8;">{{ $u->created_at->format('d M Y') }}</td>
                            <td>
                                @if($u->is_active)
                                <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#16a34a;"><span style="width:5px;height:5px;border-radius:50%;background:#16a34a;"></span>Active</span>
                                @else
                                <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#dc2626;"><span style="width:5px;height:5px;border-radius:50%;background:#dc2626;"></span>Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="ov-empty">No users yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="bi-card">
                    <div class="bi-card-title"><span class="bi-dot" style="background:#ef4444;"></span>Disease Alert Monitor</div>
                    @if(!empty($diseaseAlerts))
                    <div class="space-y-2">
                    @foreach($diseaseAlerts as $a)
                    <div style="background:{{ $a['severity']==='high'?'#fef2f2':($a['severity']==='medium'?'#fffbeb':'#f0fdf4') }};border-left:3px solid {{ $a['severity']==='high'?'#ef4444':($a['severity']==='medium'?'#f59e0b':'#22c55e') }};border-radius:8px;padding:10px 14px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#0f172a;">{{ $a['disease'] }}</div>
                                <div style="font-size:10px;color:#64748b;margin-top:2px;">{{ $a['cases'] }} {{ Str::plural('case',$a['cases']) }} · 30 days · <span style="text-transform:capitalize;">{{ $a['type']??'Unknown' }}</span></div>
                            </div>
                            <span style="background:{{ $a['severity']==='high'?'#fecaca':($a['severity']==='medium'?'#fde68a':'#bbf7d0') }};color:{{ $a['severity']==='high'?'#991b1b':($a['severity']==='medium'?'#92400e':'#14532d') }};border-radius:99px;padding:2px 8px;font-size:9px;font-weight:700;text-transform:capitalize;white-space:nowrap;">{{ $a['severity'] }}</span>
                        </div>
                    </div>
                    @endforeach
                    </div>
                    @else
                    <div class="ov-empty">No active disease alerts</div>
                    @endif
                </div>
            </div>

            <div style="text-align:right;"><a href="{{ route('ceo.system') }}" class="view-module-link">View Full System →</a></div>
        </div>
    </div>

    </div>

    <script>
    const _ovBaseOpts = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };
    function _ovGrad(ctx, top, bot) {
        const { ctx:c, chartArea } = ctx.chart;
        if (!chartArea) return top;
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, top); g.addColorStop(1, bot);
        return g;
    }

    // ── Financial: Revenue Trend (daily/weekly/monthly toggle) ──────────────
    const ovRevData = {!! json_encode([
        'daily'   => $revTimeSeries['daily']->values()->toArray(),
        'weekly'  => $revTimeSeries['weekly']->values()->toArray(),
        'monthly' => $revTimeSeries['monthly']->values()->toArray(),
    ], JSON_HEX_TAG | JSON_HEX_AMP) !!};
    let ovRevChart;
    function ovSetRevPeriod(period) {
        document.querySelectorAll('[id^="ov-rev-btn-"]').forEach(b => {
            const on = b.id === 'ov-rev-btn-' + period;
            b.style.background = on ? '#0F6B3E' : '#fff';
            b.style.color      = on ? '#fff'    : '#64748b';
        });
        const pts = ovRevData[period];
        ovRevChart.data.labels           = pts.map(p => p.label);
        ovRevChart.data.datasets[0].data = pts.map(p => p.value);
        ovRevChart.update();
    }
    ovRevChart = new Chart(document.getElementById('ov-revenueTimeChart'), {
        type: 'line',
        data: {
            labels: ovRevData.monthly.map(p => p.label),
            datasets: [{
                label: 'Revenue (₦)',
                data:  ovRevData.monthly.map(p => p.value),
                borderColor: '#0F6B3E', borderWidth: 2.5,
                backgroundColor: ctx => _ovGrad(ctx, 'rgba(15,107,62,0.35)', 'rgba(15,107,62,0.02)'),
                fill: true, tension: 0.4,
                pointRadius: 4, pointBackgroundColor: '#0F6B3E', pointBorderColor: '#fff', pointBorderWidth: 2,
            }]
        },
        options: { ..._ovBaseOpts,
            scales: {
                y: { beginAtZero:true, grid:{ color:'#f1f5f9' }, ticks:{ font:{size:10}, callback: v => '₦'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                x: { grid:{ display:false }, ticks:{ font:{size:10} } }
            },
            plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: c => ' ₦'+c.parsed.y.toLocaleString() } } }
        }
    });

    // ── AI Analytics: scan trend (filtered) ──────────────────────────────────
    const ovDailyLabels = {!! json_encode($ovDailySeries->pluck('label'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    const ovDailyValues = {!! json_encode($ovDailySeries->pluck('value'), JSON_HEX_TAG | JSON_HEX_AMP) !!};
    new Chart(document.getElementById('ov-dailyScanChart'), {
        type: 'bar',
        data: { labels: ovDailyLabels, datasets: [{ data: ovDailyValues, backgroundColor: 'rgba(37,99,235,0.75)', borderRadius: 3 }] },
        options: { ..._ovBaseOpts,
            scales: { x: { ticks:{ font:{size:9}, maxRotation:0, autoSkip:true }, grid:{ display:false } }, y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } } }
        }
    });

    // ── Geographic: Users vs Scans by state ──────────────────────────────────
    @if($geoChart->isNotEmpty())
    new Chart(document.getElementById('ov-geoBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($geoChart->keys()->toArray(), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                { label:'Users', data:{!! json_encode($geoChart->pluck('users')->toArray(),     JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(15,107,62,0.8)',  borderRadius:4 },
                { label:'Scans', data:{!! json_encode($geoChart->pluck('diagnoses')->toArray(), JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(37,99,235,0.65)', borderRadius:4 },
            ]
        },
        options: { ..._ovBaseOpts,
            scales: { x: { ticks:{ font:{size:10} }, grid:{ display:false } }, y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } } },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });
    @endif

    // ── Users & Subscriptions: growth line + plan donut ─────────────────────
    new Chart(document.getElementById('ov-userGrowthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyGrowth->pluck('label'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                { label:'Farmers', data: {!! json_encode($monthlyGrowth->pluck('farmers'), JSON_HEX_TAG | JSON_HEX_AMP) !!}, borderColor:'#0F6B3E', borderWidth:2, backgroundColor: ctx => _ovGrad(ctx,'rgba(15,107,62,0.30)','rgba(15,107,62,0.02)'), fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#0F6B3E' },
                { label:'Experts', data: {!! json_encode($monthlyGrowth->pluck('experts'), JSON_HEX_TAG | JSON_HEX_AMP) !!}, borderColor:'#2563eb', borderWidth:2, backgroundColor: ctx => _ovGrad(ctx,'rgba(37,99,235,0.20)','rgba(37,99,235,0.01)'), fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#2563eb' }
            ]
        },
        options: { ..._ovBaseOpts,
            scales: { y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } }, x: { ticks:{ font:{size:10} }, grid:{ display:false } } },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });

    @php
    $spKeys   = array_keys($subStats['by_plan']);
    $spVals   = array_values($subStats['by_plan']);
    $spNames  = ['basic'=>'Basic','basic_pro'=>'Basic Pro','premium'=>'Premium','enterprise'=>'Enterprise','enterprise_plus'=>'Ent. Plus','pro'=>'Pro'];
    $spClrs   = ['basic'=>'#16a34a','basic_pro'=>'#0D9488','premium'=>'#2563eb','enterprise'=>'#7c3aed','enterprise_plus'=>'#0B2447','pro'=>'#64748b'];
    $spLabels = array_map(fn($k) => $spNames[$k] ?? $k, $spKeys);
    $spColors = array_map(fn($k) => $spClrs[$k]  ?? '#94a3b8', $spKeys);
    @endphp
    @if(!empty($spVals) && array_sum($spVals) > 0)
    new Chart(document.getElementById('ov-subPlanDonut'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($spLabels, JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [{ data: {!! json_encode($spVals, JSON_HEX_TAG | JSON_HEX_AMP) !!}, backgroundColor: {!! json_encode($spColors, JSON_HEX_TAG | JSON_HEX_AMP) !!}, borderWidth: 0, hoverOffset: 6 }]
        },
        options: { ..._ovBaseOpts, cutout:'68%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c => ` ${c.label}: ${c.parsed}` } } } }
    });
    @endif
    </script>
</x-app-layout>
