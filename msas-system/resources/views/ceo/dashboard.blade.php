<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h2 class="font-extrabold text-xl text-gray-800 tracking-tight">Executive Command Center</h2>
                <p class="text-xs text-gray-400 mt-0.5 font-medium">MSAS FarmAI &nbsp;·&nbsp; Real-time Business Intelligence</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="live-clock" class="text-sm font-bold text-slate-600 tabular-nums bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200"></span>
                <a href="{{ route('ceo.reports') }}" class="text-xs font-semibold text-white bg-[#0F6B3E] hover:bg-[#0B2447] px-4 py-2 rounded-lg transition-colors">Export Reports →</a>
            </div>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
    /* ── Executive BI Dashboard ───────────────────────────────── */
    .bi-nav-bar { position:sticky; top:0; z-index:40; background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 1px 4px rgba(0,0,0,.06); overflow-x:auto; }
    .bi-nav-bar::-webkit-scrollbar { display:none; }
    .bi-nav-inner { display:flex; min-width:max-content; }
    .bi-nav-link { padding:11px 16px; font-size:11px; font-weight:700; color:#64748b; white-space:nowrap; border-bottom:2px solid transparent; text-decoration:none; transition:color .15s, border-color .15s; letter-spacing:.03em; text-transform:uppercase; }
    .bi-nav-link:hover { color:#0F6B3E; }
    .bi-nav-link.bi-active { color:#0F6B3E; border-bottom-color:#0F6B3E; }
    .bi-card { background:#fff; border-radius:14px; border:1px solid #f1f5f9; box-shadow:0 1px 4px rgba(0,0,0,.05); padding:20px; }
    .bi-eyebrow { font-size:9px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#94a3b8; margin-bottom:4px; }
    .bi-section-eyebrow { font-size:9px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#94a3b8; margin-bottom:12px; padding-left:2px; }
    .bi-num { font-size:26px; font-weight:900; line-height:1.1; font-variant-numeric:tabular-nums; }
    .bi-sub { font-size:11px; color:#94a3b8; margin-top:4px; font-weight:500; }
    .bi-card-title { font-size:13px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; margin-bottom:16px; }
    .bi-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .risk-crit { background:#fef2f2; border:1px solid #fecaca; border-left:4px solid #ef4444; border-radius:8px; }
    .risk-warn { background:#fffbeb; border:1px solid #fde68a; border-left:4px solid #f59e0b; border-radius:8px; }
    .risk-ok   { background:#f0fdf4; border:1px solid #bbf7d0; border-left:4px solid #22c55e; border-radius:8px; }
    .spill { font-size:10px; font-weight:700; padding:2px 9px; border-radius:99px; display:inline-flex; align-items:center; gap:4px; }
    .trend-u { color:#16a34a; font-size:11px; font-weight:700; }
    .trend-d { color:#dc2626; font-size:11px; font-weight:700; }
    </style>

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

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    {{-- ═══════════════════════════════════════════════════════════
         1. WELCOME BANNER
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
                <div class="text-center px-4 py-2.5 rounded-xl min-w-[80px]" style="background:rgba(255,255,255,0.1);backdrop-filter:blur(8px);">
                    <div class="text-lg font-black leading-none">{{ $bv }}</div>
                    <div class="text-[9px] text-emerald-200 font-semibold mt-1 uppercase tracking-wider">{{ $bk }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         2. SECTION NAVIGATION (sticky)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="bi-nav-bar rounded-xl" id="bi-nav">
        <div class="bi-nav-inner">
            <a class="bi-nav-link bi-active" href="#s-pulse"       onclick="navTo('s-pulse',this);return false;">Overview</a>
            <a class="bi-nav-link"           href="#s-risk"        onclick="navTo('s-risk',this);return false;">Risk Center</a>
            <a class="bi-nav-link"           href="#s-financial"   onclick="navTo('s-financial',this);return false;">Financial</a>
            <a class="bi-nav-link"           href="#s-ai"          onclick="navTo('s-ai',this);return false;">AI Analytics</a>
            <a class="bi-nav-link"           href="#s-marketplace" onclick="navTo('s-marketplace',this);return false;">Marketplace</a>
            <a class="bi-nav-link"           href="#s-operations"  onclick="navTo('s-operations',this);return false;">Operations</a>
            <a class="bi-nav-link"           href="#s-geo"         onclick="navTo('s-geo',this);return false;">Geographic</a>
            <a class="bi-nav-link"           href="#s-users"       onclick="navTo('s-users',this);return false;">Users & Subs</a>
            <a class="bi-nav-link"           href="#s-system"      onclick="navTo('s-system',this);return false;">System</a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         3. EXECUTIVE PULSE — 10 KPIs
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-pulse">
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

    {{-- ═══════════════════════════════════════════════════════════
         4. RISK & ALERT CENTER
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

    <div id="s-risk" class="bi-card" style="padding:16px 20px;">
        <div class="bi-card-title" style="margin-bottom:12px;">
            <span class="bi-dot" style="background:#ef4444;"></span>
            Risk & Alert Center
            <div class="ml-auto flex gap-2 flex-wrap">
                @if(count($criticals) > 0)
                <span class="spill" style="background:#fef2f2;color:#dc2626;">{{ count($criticals) }} Critical</span>
                @endif
                @if(count($warnings) > 0)
                <span class="spill" style="background:#fffbeb;color:#b45309;">{{ count($warnings) }} Warning</span>
                @endif
                @if(count($criticals) === 0 && count($warnings) === 0)
                <span class="spill" style="background:#f0fdf4;color:#16a34a;">All Systems Operational</span>
                @endif
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
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         5. FINANCIAL INTELLIGENCE
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-financial" class="space-y-4">
        <div class="bi-section-eyebrow">Financial Intelligence</div>

        {{-- Revenue time bands --}}
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

        {{-- MRR / ARR / Churn / Conversion --}}
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

        {{-- Revenue area chart (full width) + toggle --}}
        <div class="bi-card">
            <div class="bi-card-title">
                <span class="bi-dot" style="background:#0F6B3E;"></span>
                Revenue Trend
                <div class="ml-auto flex gap-1">
                @foreach(['daily'=>'14 Days','weekly'=>'8 Weeks','monthly'=>'12 Months'] as $period => $label)
                <button onclick="setRevPeriod('{{ $period }}')" id="rev-btn-{{ $period }}"
                    style="font-size:10px;font-weight:700;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;transition:all .15s;background:{{ $period==='monthly'?'#0F6B3E':'#fff' }};color:{{ $period==='monthly'?'#fff':'#64748b' }};">
                    {{ $label }}
                </button>
                @endforeach
                </div>
            </div>
            <div style="height:220px;position:relative;">
                <canvas id="revenueTimeChart"></canvas>
            </div>
        </div>

        {{-- Wallet + Subscription Revenue --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Wallet & Withdrawals</div>
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
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         6. AI ANALYTICS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-ai" class="space-y-4">
        <div class="bi-section-eyebrow">AI Smart Scan Analytics</div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
        $aiKpis = [
            ['Scans Today',      number_format($aiStats['today']),      '#0D9488','#f0fdfa'],
            ['Scans This Month', number_format($aiStats['this_month']), '#2563eb','#eff6ff'],
            ['Total Scans',      number_format($aiStats['total']),      '#0f172a','#f8fafc'],
            ['Avg Confidence',   $aiStats['avg_conf'].'%',              $aiStats['avg_conf']>=75?'#16a34a':($aiStats['avg_conf']>=50?'#d97706':'#dc2626'),'#fff'],
        ];
        @endphp
        @foreach($aiKpis as [$al,$av,$ac,$abg])
        <div class="bi-card text-center" style="background:{{ $abg }};border-top:3px solid {{ $ac }};padding:16px;">
            <div class="bi-eyebrow">{{ $al }}</div>
            <div class="bi-num mt-1" style="color:{{ $ac }};font-size:26px;">{{ $av }}</div>
        </div>
        @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Scan type split --}}
            <div class="bi-card flex flex-col items-center">
                <div class="bi-card-title self-start"><span class="bi-dot" style="background:#0D9488;"></span>Diagnosis Split</div>
                <div style="position:relative;width:150px;height:150px;">
                    <canvas id="diagnosisDonut"></canvas>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                        <span style="font-size:20px;font-weight:900;color:#0f172a;">{{ number_format($totalDiagnoses) }}</span>
                        <span style="font-size:10px;color:#94a3b8;">Total</span>
                    </div>
                </div>
                @php
                $scanTotal2 = max(1,$aiStats['crop_total']+$aiStats['live_total']+$aiStats['soil_total']);
                $scanTypes2 = [['Crop',$aiStats['crop_total'],'#0F6B3E'],['Livestock',$aiStats['live_total'],'#f59e0b'],['Soil',$aiStats['soil_total'],'#8b5cf6']];
                @endphp
                <div class="w-full mt-3 space-y-2">
                @foreach($scanTypes2 as [$sl,$sc,$sclr])
                @php $sp2 = round($sc/$scanTotal2*100); @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="color:#374151;font-weight:600;">{{ $sl }}</span>
                        <span style="font-weight:700;color:{{ $sclr }};">{{ $sc }} ({{ $sp2 }}%)</span>
                    </div>
                    <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $sp2 }}%;background:{{ $sclr }};border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>

            {{-- AI Confidence SVG gauge --}}
            <div class="bi-card text-center">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0D9488;"></span>Confidence Score</div>
                @php
                $conf  = $aiStats['avg_conf'];
                $cR    = 58; $cCirc = 2 * M_PI * $cR;
                $cFill = $cCirc * ($conf / 100);
                $cClr  = $conf >= 75 ? '#16a34a' : ($conf >= 50 ? '#d97706' : '#dc2626');
                @endphp
                <div style="display:flex;justify-content:center;margin:4px 0 12px;">
                    <svg width="150" height="150" viewBox="0 0 150 150">
                        <circle cx="75" cy="75" r="{{ $cR }}" fill="none" stroke="#f1f5f9" stroke-width="11"/>
                        <circle cx="75" cy="75" r="{{ $cR }}" fill="none" stroke="{{ $cClr }}" stroke-width="11"
                            stroke-dasharray="{{ round($cFill,2) }} {{ round($cCirc,2) }}"
                            stroke-linecap="round" transform="rotate(-90 75 75)"/>
                        <text x="75" y="70" text-anchor="middle" font-size="26" font-weight="900" fill="{{ $cClr }}">{{ $conf }}%</text>
                        <text x="75" y="87" text-anchor="middle" font-size="10" fill="#94a3b8">avg confidence</text>
                    </svg>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach([['Crop',$aiStats['crop_total'],'#0F6B3E','#f0fdf4'],['Livestock',$aiStats['live_total'],'#f59e0b','#fffbeb'],['Soil',$aiStats['soil_total'],'#8b5cf6','#f5f3ff']] as [$tl,$tv,$tc,$tbg])
                    <div style="background:{{ $tbg }};border-radius:8px;padding:8px;">
                        <div style="font-size:8px;font-weight:800;color:{{ $tc }};text-transform:uppercase;letter-spacing:.08em;">{{ $tl }}</div>
                        <div style="font-size:18px;font-weight:900;color:{{ $tc }};margin-top:2px;">{{ $tv }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Top detected diseases --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#ef4444;"></span>Top Diseases (30 days)</div>
                @forelse($aiStats['top_diseases'] as $td)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;':'' }}">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:18px;height:18px;border-radius:50%;background:#fef2f2;font-size:9px;font-weight:800;color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $loop->iteration }}</span>
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ Str::limit($td->disease_name, 28) }}</span>
                    </div>
                    <span style="font-size:11px;font-weight:800;color:#dc2626;background:#fef2f2;padding:2px 8px;border-radius:99px;">{{ $td->cnt }}</span>
                </div>
                @empty
                <div style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">No scan data yet</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         7. MARKETPLACE INTELLIGENCE
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-marketplace" class="space-y-4">
        <div class="bi-section-eyebrow">Marketplace Intelligence</div>

        <div class="bi-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                <div class="bi-card-title" style="margin-bottom:0;">
                    <span class="bi-dot" style="background:#4f46e5;"></span>Order Pipeline
                </div>
                <a href="{{ route('ceo.orders') }}" style="font-size:11px;font-weight:700;color:#4f46e5;text-decoration:none;">Manage Orders →</a>
            </div>

            {{-- Order status funnel --}}
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
                {{-- GMV --}}
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
                {{-- Top products --}}
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
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         8. OPERATIONS — Logistics + Consultations + Staff
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-operations" class="space-y-4">
        <div class="bi-section-eyebrow">Operations Intelligence</div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Logistics --}}
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#ea580c;"></span>Logistics & Delivery</div>
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
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                        <span style="color:#64748b;">Rider utilisation</span>
                        <span style="font-weight:800;color:#ea580c;">{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}% active</span>
                    </div>
                    <div style="height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;background:linear-gradient(90deg,#ea580c,#f97316);border-radius:4px;width:{{ round($logisticsStats['riders_busy']/$totalRiders*100) }}%;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-top:4px;">
                        <span>{{ $logisticsStats['riders_busy'] }} busy</span>
                        <span>{{ $logisticsStats['riders_available'] }} available</span>
                        <span>{{ $totalRiders }} total</span>
                    </div>
                </div>
            </div>

            {{-- Consultations --}}
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
                @php $cTotal = max(1,$consultStats['pending']+$consultStats['in_progress']+$consultStats['completed']); @endphp
                <div style="padding-top:10px;border-top:1px solid #f1f5f9;space-y:4px;">
                @foreach([['Pending',$consultStats['pending'],'#d97706'],['In Progress',$consultStats['in_progress'],'#2563eb'],['Completed',$consultStats['completed'],'#16a34a']] as [$rl,$rv,$rc])
                @php $rp = round($rv/$cTotal*100); @endphp
                <div style="margin-bottom:6px;">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="color:#64748b;">{{ $rl }}</span>
                        <span style="font-weight:700;color:{{ $rc }};">{{ $rv }}</span>
                    </div>
                    <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $rp }}%;background:{{ $rc }};border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- Staff & HR quick panel --}}
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#475569;"></span>Staff & HR</div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
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
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @php
                $quickLinks = [
                    [route('ceo.staff.create'),       'Add Staff',    '#16a34a','#f0fdf4','M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                    [route('ceo.staff.index'),         'All Staff',    '#475569','#f8fafc','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    [route('ceo.staff-roles.index'),   'Roles & Perms','#2563eb','#eff6ff','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    [route('ceo.audit'),               'Audit Log',    '#d97706','#fffbeb','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ];
                @endphp
                @foreach($quickLinks as [$ql_href,$ql_label,$ql_c,$ql_bg,$ql_icon])
                <a href="{{ $ql_href }}" style="display:flex;flex-direction:column;align-items:center;padding:14px;background:{{ $ql_bg }};border-radius:10px;text-decoration:none;transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                    <svg style="width:22px;height:22px;color:{{ $ql_c }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ql_icon }}"/></svg>
                    <span style="font-size:11px;font-weight:700;color:{{ $ql_c }};margin-top:5px;">{{ $ql_label }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         9. GEOGRAPHIC INTELLIGENCE
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-geo" class="space-y-4">
        <div class="bi-section-eyebrow">Geographic Intelligence</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>Top States — Users &amp; AI Scans</div>
                <div style="height:200px;position:relative;">
                    <canvas id="geoBarChart"></canvas>
                </div>
                @if($geoChart->isEmpty())
                <p style="font-size:13px;color:#94a3b8;text-align:center;padding:12px;">No state data yet</p>
                @endif
            </div>
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#7c3aed;"></span>State Activity Ranking</div>
                @if(!empty($stateActivity))
                @php $maxState = max($stateActivity ?: [1]); @endphp
                <div class="space-y-2.5">
                @foreach($stateActivity as $state => $cnt)
                @php $sp = $maxState > 0 ? round(($cnt/$maxState)*100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="font-weight:600;color:#374151;">{{ $state }}</span>
                        <span style="font-weight:800;color:#7c3aed;">{{ $cnt }}</span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $sp }}%;background:linear-gradient(90deg,#7c3aed,#6d28d9);border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
                @else
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:120px;color:#cbd5e1;">
                    <svg style="width:36px;height:36px;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span style="font-size:13px;">No state data yet</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         10. USERS & SUBSCRIPTION ANALYTICS
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-users" class="space-y-4">
        <div class="bi-section-eyebrow">User & Subscription Analytics</div>

        {{-- User registration KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @php
        $userKpis = [
            ['New Today',     $newUsersToday, '#16a34a','#f0fdf4'],
            ['New This Week', $newUsersWeek,  '#2563eb','#eff6ff'],
            ['Total Users',   $totalUsers,    '#0f172a','#f8fafc'],
            ['Verified',      $verifiedUsers, '#7c3aed','#f5f3ff'],
            ['Verify Rate',   $verifyRate.'%',($verifyRate>=80?'#16a34a':($verifyRate>=50?'#d97706':'#dc2626')),'#fff'],
        ];
        @endphp
        @foreach($userKpis as [$ul,$uv,$uc,$ubg])
        <div class="bi-card text-center" style="background:{{ $ubg }};border-top:3px solid {{ $uc }};padding:14px;">
            <div class="bi-eyebrow">{{ $ul }}</div>
            <div class="bi-num mt-1" style="color:{{ $uc }};font-size:22px;">{{ number_format($uv) }}</div>
        </div>
        @endforeach
        </div>

        {{-- Growth chart + role breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bi-card">
                <div class="bi-card-title"><span class="bi-dot" style="background:#0F6B3E;"></span>User Growth (6 Months)</div>
                <div style="height:200px;position:relative;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
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
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span style="font-weight:600;color:#374151;text-transform:capitalize;">{{ str_replace('-',' ',$role) }}</span>
                        <span style="font-weight:800;color:{{ $clr }};">{{ $cnt }} <span style="color:#94a3b8;font-weight:400;">({{ $pct }}%)</span></span>
                    </div>
                    <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $clr }};border-radius:3px;"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- Subscription analytics --}}
        <div class="bi-card">
            <div class="bi-card-title"><span class="bi-dot" style="background:#be185d;"></span>Subscription Analytics</div>

            {{-- Status row --}}
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
                {{-- Plan doughnut --}}
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="position:relative;width:140px;height:140px;flex-shrink:0;">
                        <canvas id="subPlanDonut"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                            <span style="font-size:20px;font-weight:900;color:#0f172a;">{{ array_sum($subStats['by_plan']) ?: $subStats['active'] }}</span>
                            <span style="font-size:10px;color:#94a3b8;">Active</span>
                        </div>
                    </div>
                    <div style="flex:1;min-width:120px;">
                    @php $planDefs=['basic'=>['#16a34a','Basic'],'basic_pro'=>['#0D9488','Basic Pro'],'premium'=>['#2563eb','Premium'],'enterprise'=>['#7c3aed','Enterprise'],'enterprise_plus'=>['#0B2447','Ent. Plus'],'pro'=>['#64748b','Pro']]; @endphp
                    @foreach($planDefs as $pk => [$pc,$pn])
                    @php $cnt2 = $subStats['by_plan'][$pk] ?? 0; @endphp
                    @if($cnt2 > 0)
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;margin-bottom:7px;">
                        <span style="display:flex;align-items:center;gap:6px;color:#374151;font-weight:500;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $pc }};flex-shrink:0;display:inline-block;"></span>
                            {{ $pn }}
                        </span>
                        <span style="font-weight:800;color:{{ $pc }};">{{ $cnt2 }}</span>
                    </div>
                    @endif
                    @endforeach
                    </div>
                </div>

                {{-- Plan bars + growth --}}
                <div>
                    <div class="bi-eyebrow" style="margin-bottom:8px;">Subscribers by Plan</div>
                    @foreach($planColors as $pk => [$pc,$pn])
                    @php $cnt3=$subStats['by_plan'][$pk]??0; $subTot=max(1,$subStats['active']+$subStats['trial']); $pct3=round($cnt3/$subTot*100); @endphp
                    @if($cnt3 > 0 || in_array($pk, ['basic','basic_pro','premium']))
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                            <span style="color:#374151;font-weight:600;">{{ $pn }}</span>
                            <span style="font-weight:800;color:{{ $pc }};">{{ $cnt3 }}</span>
                        </div>
                        <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pct3 }}%;background:{{ $pc }};border-radius:3px;"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                    <div style="margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;font-size:12px;">
                        <span style="color:#64748b;">New this month</span>
                        <span style="font-weight:800;color:{{ $subStats['growth_pct']>=0?'#16a34a':'#dc2626' }};">
                            {{ $subStats['new_this_month'] }}
                            ({{ $subStats['growth_pct']>=0?'↑':'↓' }}{{ abs($subStats['growth_pct']) }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         11. SYSTEM HEALTH & RECENT ACTIVITY
    ═══════════════════════════════════════════════════════════ --}}
    <div id="s-system" class="space-y-4">
        <div class="bi-section-eyebrow">System Health & Recent Activity</div>

        {{-- Performance gauges --}}
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
                    <span style="font-weight:800;color:{{ $gok?'#16a34a':'#d97706' }};">{{ $gv }}%
                        <span style="font-size:10px;color:#94a3b8;font-weight:400;">/ {{ $gt }}% target</span>
                    </span>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;background:{{ $gc }};border-radius:4px;width:{{ $gp }}%;"></div>
                </div>
            </div>
            @endforeach
            </div>
        </div>

        {{-- Recent registrations + Disease alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <div class="bi-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div class="bi-card-title" style="margin-bottom:0;"><span class="bi-dot" style="background:#0F6B3E;"></span>Recent Registrations</div>
                    <a href="{{ route('ceo.users') }}" style="font-size:11px;font-weight:700;color:#0F6B3E;text-decoration:none;">View all →</a>
                </div>
                <div style="overflow-x:auto;">
                <table style="width:100%;font-size:12px;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #f1f5f9;">
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Name</th>
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Role</th>
                            <th style="text-align:left;padding:0 8px 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Joined</th>
                            <th style="text-align:left;padding:0 0 8px 0;font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentUsers as $u)
                    <tr style="border-bottom:1px solid #f8fafc;">
                        <td style="padding:8px 8px 8px 0;font-weight:700;color:#0f172a;">{{ $u->first_name }} {{ $u->last_name }}</td>
                        <td style="padding:8px 8px 8px 0;">
                            <span style="background:#f0fdf4;color:#16a34a;border-radius:99px;padding:2px 7px;font-size:10px;font-weight:700;text-transform:capitalize;white-space:nowrap;">{{ str_replace('-',' ',$u->role) }}</span>
                        </td>
                        <td style="padding:8px 8px 8px 0;color:#94a3b8;white-space:nowrap;">{{ $u->created_at->format('d M Y') }}</td>
                        <td style="padding:8px 0;">
                            @if($u->is_active)
                            <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#16a34a;"><span style="width:5px;height:5px;border-radius:50%;background:#16a34a;flex-shrink:0;"></span>Active</span>
                            @else
                            <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;color:#dc2626;"><span style="width:5px;height:5px;border-radius:50%;background:#dc2626;flex-shrink:0;"></span>Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="padding:24px;text-align:center;color:#94a3b8;">No users yet</td></tr>
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
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:110px;color:#cbd5e1;">
                    <svg style="width:32px;height:32px;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span style="font-size:12px;font-weight:600;">No active disease alerts</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    </div>{{-- /max-w wrapper --}}

    <script>
    // ── Live clock (Africa/Lagos) ───────────────────────────────────
    (function tick() {
        const el = document.getElementById('live-clock');
        if (el) el.textContent = new Date().toLocaleTimeString('en-NG', {
            hour:'2-digit', minute:'2-digit', second:'2-digit', timeZone:'Africa/Lagos'
        });
        setTimeout(tick, 1000);
    })();

    // ── Section navigation ──────────────────────────────────────────
    function navTo(id, el) {
        event.preventDefault();
        document.querySelectorAll('.bi-nav-link').forEach(a => a.classList.remove('bi-active'));
        el.classList.add('bi-active');
        const t = document.getElementById(id);
        if (t) { const y = t.getBoundingClientRect().top + window.scrollY - 80; window.scrollTo({ top: y, behavior: 'smooth' }); }
    }

    // Scrollspy
    const _navSections = ['s-pulse','s-risk','s-financial','s-ai','s-marketplace','s-operations','s-geo','s-users','s-system'];
    const _navLinks    = document.querySelectorAll('.bi-nav-link');
    const _obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const id = e.target.id;
            _navLinks.forEach(a => a.classList.toggle('bi-active', a.getAttribute('href') === '#' + id));
        });
    }, { rootMargin:'-35% 0px -55% 0px' });
    _navSections.forEach(id => { const el = document.getElementById(id); if (el) _obs.observe(el); });

    // ── Chart helpers ───────────────────────────────────────────────
    const _baseOpts = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };

    function _grad(ctx, top, bot) {
        const { ctx:c, chartArea } = ctx.chart;
        if (!chartArea) return top;
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, top); g.addColorStop(1, bot);
        return g;
    }

    // ── Revenue Area Chart ──────────────────────────────────────────
    const revData = {!! json_encode([
        'daily'   => $revTimeSeries['daily']->values()->toArray(),
        'weekly'  => $revTimeSeries['weekly']->values()->toArray(),
        'monthly' => $revTimeSeries['monthly']->values()->toArray(),
    ], JSON_HEX_TAG | JSON_HEX_AMP) !!};

    let revChart;
    function setRevPeriod(period) {
        document.querySelectorAll('[id^="rev-btn-"]').forEach(b => {
            const on = b.id === 'rev-btn-' + period;
            b.style.background = on ? '#0F6B3E' : '#fff';
            b.style.color      = on ? '#fff'    : '#64748b';
        });
        const pts = revData[period];
        revChart.data.labels                   = pts.map(p => p.label);
        revChart.data.datasets[0].data         = pts.map(p => p.value);
        revChart.update();
    }
    revChart = new Chart(document.getElementById('revenueTimeChart'), {
        type: 'line',
        data: {
            labels: revData.monthly.map(p => p.label),
            datasets: [{
                label: 'Revenue (₦)',
                data:  revData.monthly.map(p => p.value),
                borderColor: '#0F6B3E', borderWidth: 2.5,
                backgroundColor: ctx => _grad(ctx, 'rgba(15,107,62,0.35)', 'rgba(15,107,62,0.02)'),
                fill: true, tension: 0.4,
                pointRadius: 4, pointBackgroundColor: '#0F6B3E', pointBorderColor: '#fff', pointBorderWidth: 2,
            }]
        },
        options: { ..._baseOpts,
            scales: {
                y: { beginAtZero:true, grid:{ color:'#f1f5f9' }, ticks:{ font:{size:10}, callback: v => '₦'+(v>=1000?(v/1000).toFixed(0)+'k':v) } },
                x: { grid:{ display:false }, ticks:{ font:{size:10} } }
            },
            plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: c => ' ₦'+c.parsed.y.toLocaleString() } } }
        }
    });

    // ── User Growth Area Chart ──────────────────────────────────────
    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyGrowth->pluck('label'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                {
                    label:'Farmers',
                    data: {!! json_encode($monthlyGrowth->pluck('farmers'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
                    borderColor:'#0F6B3E', borderWidth:2,
                    backgroundColor: ctx => _grad(ctx,'rgba(15,107,62,0.30)','rgba(15,107,62,0.02)'),
                    fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#0F6B3E',
                },
                {
                    label:'Experts',
                    data: {!! json_encode($monthlyGrowth->pluck('experts'), JSON_HEX_TAG | JSON_HEX_AMP) !!},
                    borderColor:'#2563eb', borderWidth:2,
                    backgroundColor: ctx => _grad(ctx,'rgba(37,99,235,0.20)','rgba(37,99,235,0.01)'),
                    fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#2563eb',
                }
            ]
        },
        options: { ..._baseOpts,
            scales: {
                y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } },
                x: { ticks:{ font:{size:10} }, grid:{ display:false } }
            },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });

    // ── Diagnosis Doughnut ──────────────────────────────────────────
    @php
    $dCrop = $cropDiagnoses ?: max(1, $totalDiagnoses > 0 ? $totalDiagnoses : 1);
    $dLive = $livestockDiagnoses ?: 1;
    $dSoil = $aiStats['soil_total'] ?: 0;
    @endphp
    new Chart(document.getElementById('diagnosisDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Crop', 'Livestock', 'Soil'],
            datasets: [{
                data: [{{ $dCrop }}, {{ $dLive }}, {{ $dSoil ?: 1 }}],
                backgroundColor: ['#0F6B3E','#f59e0b','#8b5cf6'],
                borderWidth: 0, hoverOffset: 6
            }]
        },
        options: { ..._baseOpts, cutout:'70%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c => ` ${c.label}: ${c.parsed}` } } } }
    });

    // ── Subscription Plan Doughnut ──────────────────────────────────
    @php
    $spKeys   = array_keys($subStats['by_plan']);
    $spVals   = array_values($subStats['by_plan']);
    $spNames  = ['basic'=>'Basic','basic_pro'=>'Basic Pro','premium'=>'Premium','enterprise'=>'Enterprise','enterprise_plus'=>'Ent. Plus','pro'=>'Pro'];
    $spClrs   = ['basic'=>'#16a34a','basic_pro'=>'#0D9488','premium'=>'#2563eb','enterprise'=>'#7c3aed','enterprise_plus'=>'#0B2447','pro'=>'#64748b'];
    $spLabels = array_map(fn($k) => $spNames[$k] ?? $k, $spKeys);
    $spColors = array_map(fn($k) => $spClrs[$k]  ?? '#94a3b8', $spKeys);
    @endphp
    @if(!empty($spVals) && array_sum($spVals) > 0)
    new Chart(document.getElementById('subPlanDonut'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($spLabels, JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [{
                data:            {!! json_encode($spVals,   JSON_HEX_TAG | JSON_HEX_AMP) !!},
                backgroundColor: {!! json_encode($spColors, JSON_HEX_TAG | JSON_HEX_AMP) !!},
                borderWidth: 0, hoverOffset: 6
            }]
        },
        options: { ..._baseOpts, cutout:'68%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c => ` ${c.label}: ${c.parsed}` } } } }
    });
    @endif

    // ── Geographic Bar Chart ────────────────────────────────────────
    @if($geoChart->isNotEmpty())
    new Chart(document.getElementById('geoBarChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($geoChart->keys()->toArray(), JSON_HEX_TAG | JSON_HEX_AMP) !!},
            datasets: [
                { label:'Users', data:{!! json_encode($geoChart->pluck('users')->toArray(),     JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(15,107,62,0.8)',  borderRadius:4 },
                { label:'Scans', data:{!! json_encode($geoChart->pluck('diagnoses')->toArray(), JSON_HEX_TAG|JSON_HEX_AMP) !!}, backgroundColor:'rgba(37,99,235,0.65)', borderRadius:4 },
            ]
        },
        options: { ..._baseOpts,
            scales: {
                x: { ticks:{ font:{size:10} }, grid:{ display:false } },
                y: { beginAtZero:true, ticks:{ precision:0, font:{size:10} }, grid:{ color:'#f1f5f9' } }
            },
            plugins: { legend:{ display:true, position:'top', labels:{ font:{size:10}, boxWidth:10, usePointStyle:true } } }
        }
    });
    @endif
    </script>
</x-app-layout>
