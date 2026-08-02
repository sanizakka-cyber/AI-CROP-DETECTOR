<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">System Monitoring</h2>
            <p style="font-size:12px;color:#64748b;margin:3px 0 0;">Live operational health — refreshes every 60 seconds</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <div id="last-refresh" style="font-size:11px;color:#94a3b8;"></div>
            <button onclick="refreshHealth()" style="background:#0F6B3E;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Refresh</button>
        </div>
    </div>
</x-slot>

<div style="padding:24px 0 48px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:1280px;margin:0 auto;padding:0 20px;">

{{-- ── System Health Row ─────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">

    @php
    $healthCards = [
        ['label'=>'Database', 'id'=>'db', 'ok'=>$dbStatus['ok'], 'detail'=>($dbStatus['ok'] ? $dbStatus['ms'].'ms response' : $dbStatus['label']), 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M4 7c0-1.66 3.58-3 8-3s8 1.34 8 3v2c0 1.66-3.58 3-8 3S4 10.66 4 9V7zm0 5c0 1.66 3.58 3 8 3s8-1.34 8-3m-16 0v5c0 1.66 3.58 3 8 3s8-1.34 8-3v-5"/>'],
        ['label'=>'AI Engine', 'id'=>'ai', 'ok'=>$aiStatus['ok'], 'detail'=>($aiStatus['ok'] ? $aiStatus['ms'].'ms response' : $aiStatus['label']), 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707M12 21a9 9 0 01-6.364-15.364A9 9 0 0112 3a9 9 0 016.364 15.364A9 9 0 0112 21z"/>'],
        ['label'=>'Job Queue', 'id'=>'queue', 'ok'=>$queueStatus['ok'], 'detail'=>($queueStatus['ok'] ? ($queueStatus['pending'].' pending') : $queueStatus['label']), 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'],
        ['label'=>'Payments', 'id'=>'pay', 'ok'=>($paySuccessRate >= 80), 'detail'=>$paySuccessRate.'% success rate today', 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>'],
    ];
    @endphp

    @foreach($healthCards as $card)
    <div id="card-{{ $card['id'] }}" style="background:#fff;border-radius:16px;border:2px solid {{ $card['ok'] ? '#bbf7d0' : '#fecaca' }};padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
        <div style="width:44px;height:44px;border-radius:12px;background:{{ $card['ok'] ? '#f0fdf4' : '#fef2f2' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="22" height="22" fill="none" stroke="{{ $card['ok'] ? '#059669' : '#dc2626' }}" stroke-width="1.8" viewBox="0 0 24 24">{!! $card['icon'] !!}</svg></div>
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;">{{ $card['label'] }}</div>
            <div style="font-size:15px;font-weight:800;color:{{ $card['ok'] ? '#0F6B3E' : '#dc2626' }};margin-top:2px;">{{ $card['ok'] ? 'Healthy' : 'Issue' }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ $card['detail'] }}</div>
        </div>
    </div>
    @endforeach

</div>

{{-- ── KPI Row ───────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:14px;margin-bottom:28px;">
    @php
    $kpis = [
        ['label'=>'AI Scans Today',   'value'=>$scanToday,     'sub'=>$scanWeek.' this week',       'color'=>'#0F6B3E'],
        ['label'=>'Scans This Month', 'value'=>$scanMonth,     'sub'=>$scanSuccessRate.'% success',  'color'=>'#0F6B3E'],
        ['label'=>'Avg Confidence',   'value'=>$avgConfidence.'%','sub'=>'last 30 days',             'color'=>'#7c3aed'],
        ['label'=>'Revenue Today',    'value'=>'₦'.number_format($revenueToday),'sub'=>'₦'.number_format($revenueMonth).' MTD','color'=>'#0369a1'],
        ['label'=>'New Users Today',  'value'=>$usersToday,    'sub'=>$usersWeek.' this week',       'color'=>'#0369a1'],
        ['label'=>'Active Subs',      'value'=>$activeSubCount,'sub'=>'of '.$usersTotal.' users',    'color'=>'#b45309'],
        ['label'=>'Failed Logins',    'value'=>$failedLoginsToday,'sub'=>$lockedAccountsToday.' locked today','color'=>($failedLoginsToday>10?'#dc2626':'#475569')],
        ['label'=>'Failed Jobs',      'value'=>$failedJobs,    'sub'=>$pendingJobs.' pending',       'color'=>($failedJobs>0?'#dc2626':'#475569')],
    ];
    @endphp
    @foreach($kpis as $k)
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:6px;">{{ $k['label'] }}</div>
        <div style="font-size:26px;font-weight:900;color:{{ $k['color'] }};line-height:1;">{{ $k['value'] }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ $k['sub'] }}</div>
    </div>
    @endforeach
</div>

{{-- ── Main Grid ─────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    {{-- Scan Trend Chart --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">AI Scans — Last 14 Days</div>
        <div style="display:flex;align-items:flex-end;gap:4px;height:80px;">
        @php
            $maxScan = max(1, $scanTrend->max());
            $days14  = collect(range(13,0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
        @endphp
        @foreach($days14 as $d)
        @php $v = $scanTrend->get($d, 0); $pct = round($v/$maxScan*100); @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;" title="{{ $d }}: {{ $v }} scans">
            <div style="width:100%;height:{{ max(4,$pct) }}%;background:{{ $v>0 ? 'linear-gradient(180deg,#1FA84A,#0F6B3E)' : '#e2e8f0' }};border-radius:3px 3px 0 0;transition:height 0.3s;"></div>
        </div>
        @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:9px;color:#94a3b8;">
            <span>{{ now()->subDays(13)->format('M d') }}</span>
            <span>{{ now()->format('M d') }}</span>
        </div>
    </div>

    {{-- New Users Trend --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">New Registrations — Last 14 Days</div>
        <div style="display:flex;align-items:flex-end;gap:4px;height:80px;">
        @php $maxU = max(1,$newUsersTrend->max()); @endphp
        @foreach($days14 as $d)
        @php $v = $newUsersTrend->get($d, 0); $pct = round($v/$maxU*100); @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;" title="{{ $d }}: {{ $v }} users">
            <div style="width:100%;height:{{ max(4,$pct) }}%;background:{{ $v>0 ? 'linear-gradient(180deg,#3b82f6,#1d4ed8)' : '#e2e8f0' }};border-radius:3px 3px 0 0;transition:height 0.3s;"></div>
        </div>
        @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:9px;color:#94a3b8;">
            <span>{{ now()->subDays(13)->format('M d') }}</span>
            <span>{{ now()->format('M d') }}</span>
        </div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    {{-- Security Alerts --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;">Security Events — Last 24h</div>
            @if($failedLoginsToday > 0 || $lockedAccountsToday > 0)
            <span style="background:#fef2f2;color:#dc2626;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #fecaca;">{{ $failedLoginsToday + $lockedAccountsToday }} alert(s)</span>
            @else
            <span style="background:#f0fdf4;color:#0F6B3E;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #bbf7d0;">Clear</span>
            @endif
        </div>
        @if($recentAudit->isEmpty())
        <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;">No security events in the last 24 hours.</p>
        @else
        <div style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
            @foreach($recentAudit as $log)
            <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:#fef2f2;border-radius:10px;border:1px solid #fecaca;">
                <svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div>
                    <div style="font-size:12px;font-weight:700;color:#dc2626;">{{ $log->action }}</div>
                    <div style="font-size:11px;color:#475569;">{{ $log->user?->first_name }} {{ $log->user?->last_name }} — IP: {{ $log->ip_address }}</div>
                    <div style="font-size:10px;color:#94a3b8;">{{ $log->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Failed Payments --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;">Failed Payments — Last 24h</div>
            @if($recentFailedPayments->isNotEmpty())
            <span style="background:#fef2f2;color:#dc2626;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #fecaca;">{{ $recentFailedPayments->count() }}</span>
            @else
            <span style="background:#f0fdf4;color:#0F6B3E;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #bbf7d0;">None</span>
            @endif
        </div>
        @if($recentFailedPayments->isEmpty())
        <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;">No failed payments in the last 24 hours.</p>
        @else
        <div style="display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
            @foreach($recentFailedPayments as $pay)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#fef2f2;border-radius:10px;border:1px solid #fecaca;">
                <div>
                    <div style="font-size:12px;font-weight:700;color:#dc2626;">₦{{ number_format($pay->amount) }}</div>
                    <div style="font-size:11px;color:#475569;">{{ $pay->user?->first_name }} {{ $pay->user?->last_name }}</div>
                    <div style="font-size:10px;color:#94a3b8;">{{ $pay->created_at->diffForHumans() }}</div>
                </div>
                <div style="font-size:10px;color:#94a3b8;text-align:right;">{{ $pay->reference }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

{{-- Error Log (DB-backed) --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:#0f172a;">Application Error Log — Unresolved</div>
        @if($recentErrors->isNotEmpty())
        <span style="background:#fef2f2;color:#dc2626;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #fecaca;">{{ $recentErrors->count() }} unresolved</span>
        @else
        <span style="background:#f0fdf4;color:#0F6B3E;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #bbf7d0;">No errors</span>
        @endif
    </div>

    @if($recentErrors->isEmpty())
    <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;">No unresolved application errors.</p>
    @else
    <div style="display:flex;flex-direction:column;gap:8px;max-height:400px;overflow-y:auto;">
        @foreach($recentErrors as $err)
        @php
        $catColors = [
            'database' => ['bg'=>'#fef2f2','border'=>'#fecaca','text'=>'#dc2626'],
            'payment'  => ['bg'=>'#fff7ed','border'=>'#fed7aa','text'=>'#c2410c'],
            'auth'     => ['bg'=>'#fefce8','border'=>'#fde68a','text'=>'#92400e'],
            'ai'       => ['bg'=>'#f5f3ff','border'=>'#ddd6fe','text'=>'#6d28d9'],
            'sms'      => ['bg'=>'#eff6ff','border'=>'#bfdbfe','text'=>'#1d4ed8'],
            'email'    => ['bg'=>'#eff6ff','border'=>'#bfdbfe','text'=>'#1d4ed8'],
            'otp'      => ['bg'=>'#fefce8','border'=>'#fde68a','text'=>'#92400e'],
            'app'      => ['bg'=>'#f8fafc','border'=>'#e2e8f0','text'=>'#475569'],
        ];
        $c = $catColors[$err->category] ?? $catColors['app'];
        @endphp
        <div style="background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};border-radius:10px;padding:12px 14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="background:{{ $c['border'] }};color:{{ $c['text'] }};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em;">{{ $err->category }}</span>
                    <span style="font-size:11px;color:#94a3b8;">{{ $err->created_at->diffForHumans() }}</span>
                    @if($err->method)
                    <span style="font-size:10px;font-weight:700;color:#64748b;background:#f1f5f9;padding:1px 6px;border-radius:4px;">{{ $err->method }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('ceo.monitoring.error.resolve', $err->id) }}" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:none;border:1px solid {{ $c['border'] }};color:{{ $c['text'] }};font-size:10px;font-weight:700;padding:3px 10px;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Resolve</button>
                </form>
            </div>
            <div style="font-size:12px;font-weight:700;color:{{ $c['text'] }};margin-bottom:4px;word-break:break-all;">{{ $err->exception_class }}</div>
            <div style="font-size:12px;color:#374151;margin-bottom:6px;word-break:break-all;">{{ $err->message }}</div>
            <div style="font-size:10px;color:#94a3b8;display:flex;flex-wrap:wrap;gap:12px;">
                @if($err->file) <span title="{{ $err->file }}">{{ basename($err->file) }}:{{ $err->line }}</span> @endif
                @if($err->url) <span>{{ Str::limit($err->url, 60) }}</span> @endif
                @if($err->user_id) <span>User #{{ $err->user_id }} ({{ $err->user_role }})</span> @endif
                @if($err->ip_address) <span>IP: {{ $err->ip_address }}</span> @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Revenue & Totals --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
    @php
    $totals = [
        ['label'=>'Total Revenue', 'value'=>'₦'.number_format($revenueTotal), 'sub'=>'All time', 'color'=>'#0F6B3E', 'bg'=>'#f0fdf4'],
        ['label'=>'Revenue MTD',   'value'=>'₦'.number_format($revenueMonth), 'sub'=>now()->format('F Y'), 'color'=>'#0369a1','bg'=>'#eff6ff'],
        ['label'=>'Total AI Scans','value'=>number_format($scanTotal), 'sub'=>'All time',         'color'=>'#7c3aed','bg'=>'#f5f3ff'],
        ['label'=>'Total Users',   'value'=>number_format($usersTotal),'sub'=>$activeSubCount.' paying','color'=>'#b45309','bg'=>'#fffbeb'],
    ];
    @endphp
    @foreach($totals as $t)
    <div style="background:{{ $t['bg'] }};border-radius:16px;border:1px solid #e2e8f0;padding:22px 24px;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;margin-bottom:6px;">{{ $t['label'] }}</div>
        <div style="font-size:28px;font-weight:900;color:{{ $t['color'] }};line-height:1;">{{ $t['value'] }}</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ $t['sub'] }}</div>
    </div>
    @endforeach
</div>

</div>
</div>

<script>
function refreshHealth() {
    fetch('{{ route('ceo.monitoring.health') }}')
        .then(r => r.json())
        .then(data => {
            document.getElementById('last-refresh').textContent = 'Last refresh: ' + data.time;
            updateCard('db',    data.db.ok,    data.db.ok    ? data.db.ms+'ms response'    : data.db.label);
            updateCard('ai',    data.ai.ok,    data.ai.ok    ? data.ai.ms+'ms response'    : data.ai.label);
            updateCard('queue', data.queue.ok, data.queue.ok ? data.queue.pending+' pending' : data.queue.label);
        })
        .catch(() => {});
}

function updateCard(id, ok, detail) {
    const card = document.getElementById('card-' + id);
    if (!card) return;
    const green = '#bbf7d0', red = '#fecaca';
    card.style.borderColor = ok ? green : red;
    const statusEl = card.querySelector('[data-status]');
}

document.getElementById('last-refresh').textContent = 'Last refresh: {{ now()->toTimeString() }}';
setInterval(refreshHealth, 60000);
</script>
</x-app-layout>