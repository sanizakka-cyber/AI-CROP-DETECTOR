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

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-screen-xl mx-auto space-y-5">

    @include('ceo.partials.nav')

    <x-dashboard-error-banner :errors="$dashboardErrors ?? []" />

    <div class="space-y-4">
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

    </div>

    <script>
    const _baseOpts = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };
    function _grad(ctx, top, bot) {
        const { ctx:c, chartArea } = ctx.chart;
        if (!chartArea) return top;
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, top); g.addColorStop(1, bot);
        return g;
    }

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
    </script>
</x-app-layout>
