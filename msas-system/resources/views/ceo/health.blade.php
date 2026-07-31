<x-app-layout>
<div style="max-width:1200px;margin:0 auto;padding:28px 20px">

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:28px">
    <div>
        <h1 style="font-size:22px;font-weight:800;color:#0B2447;margin-bottom:3px">System Health</h1>
        <p style="font-size:13px;color:#64748b">Real-time operational status — auto-refreshes every 30 seconds</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <span id="last-updated" style="font-size:12px;color:#94a3b8"></span>
        <button id="refresh-btn" onclick="refreshNow()"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#0F6B3E;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">
            <svg id="refresh-icon" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M4 4v5h5M20 20v-5h-5M4.07 13A8 8 0 1020 9"/>
            </svg>
            Refresh
        </button>
        <a href="{{ route('ceo.monitoring') }}"
            style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;color:#475569;text-decoration:none">
            Error Logs →
        </a>
    </div>
</div>

{{-- ── Summary strip ────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:24px" id="stats-strip">
    @php
        $statuses = collect($health)->only(['database','queue','ai','paystack','mail','storage','system','ssl','users','errors'])->pluck('status');
        $errorCount = $statuses->filter(fn($s) => $s === 'error')->count();
        $warnCount  = $statuses->filter(fn($s) => $s === 'warn')->count();
        $okCount    = $statuses->filter(fn($s) => $s === 'ok')->count();
    @endphp
    <div class="stat-card" data-stat="ok-count">
        <div class="stat-num" style="color:#0F6B3E">{{ $okCount }}</div>
        <div class="stat-lbl">Healthy</div>
    </div>
    <div class="stat-card" data-stat="warn-count">
        <div class="stat-num" style="color:#b45309">{{ $warnCount }}</div>
        <div class="stat-lbl">Warnings</div>
    </div>
    <div class="stat-card" data-stat="error-count">
        <div class="stat-num" style="color:#dc2626">{{ $errorCount }}</div>
        <div class="stat-lbl">Critical</div>
    </div>
    <div class="stat-card" data-stat="active-users">
        <div class="stat-num" style="color:#2D9CDB">{{ $health['users']['active_15min'] ?? 0 }}</div>
        <div class="stat-lbl">Active Users</div>
    </div>
    <div class="stat-card" data-stat="online-riders">
        <div class="stat-num" style="color:#7c3aed">{{ $health['users']['online_riders'] ?? 0 }}</div>
        <div class="stat-lbl">Riders Online</div>
    </div>
    <div class="stat-card" data-stat="errors-24h">
        <div class="stat-num" style="color:{{ ($health['errors']['count_24h'] ?? 0) > 20 ? '#dc2626' : '#334155' }}">{{ $health['errors']['count_24h'] ?? 0 }}</div>
        <div class="stat-lbl">Errors (24 h)</div>
    </div>
    <div class="stat-card" data-stat="queue-failed">
        <div class="stat-num" style="color:{{ ($health['queue']['failed'] ?? 0) > 0 ? '#b45309' : '#334155' }}">{{ $health['queue']['failed'] ?? 0 }}</div>
        <div class="stat-lbl">Failed Jobs</div>
    </div>
</div>

{{-- ── Service cards grid ───────────────────────────────────────────────── --}}
<div id="service-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">

    @php
    $services = [
        ['key'=>'database', 'name'=>'Database',       'icon'=>'🗄️',  'sub'=>'PostgreSQL · Query latency'],
        ['key'=>'queue',    'name'=>'Queue Worker',    'icon'=>'⚙️',  'sub'=>'Pending &amp; failed jobs'],
        ['key'=>'ai',       'name'=>'AI Engine',       'icon'=>'🧠',  'sub'=>'Crop &amp; animal diagnosis'],
        ['key'=>'paystack', 'name'=>'Paystack',        'icon'=>'💳',  'sub'=>'Payment gateway reachability'],
        ['key'=>'mail',     'name'=>'Email Service',   'icon'=>'📧',  'sub'=>'SMTP configuration'],
        ['key'=>'storage',  'name'=>'Disk Storage',    'icon'=>'💾',  'sub'=>'Application &amp; upload storage'],
        ['key'=>'system',   'name'=>'Server (CPU/RAM)','icon'=>'🖥️',  'sub'=>'Load average &amp; memory'],
        ['key'=>'ssl',      'name'=>'SSL Certificate', 'icon'=>'🔒',  'sub'=>'HTTPS certificate expiry'],
        ['key'=>'users',    'name'=>'Active Sessions', 'icon'=>'👥',  'sub'=>'Users active in last 15 min'],
        ['key'=>'errors',   'name'=>'Error Log',       'icon'=>'🚨',  'sub'=>'Application errors · last 24 h'],
    ];
    @endphp

    @foreach($services as $svc)
    @php
        $d = $health[$svc['key']] ?? [];
        $status = $d['status'] ?? 'warn';
        $colors = [
            'ok'    => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','dot'=>'#16a34a','text'=>'#166534','label'=>'Healthy'],
            'warn'  => ['bg'=>'#fffbeb','border'=>'#fde68a','dot'=>'#d97706','text'=>'#92400e','label'=>'Warning'],
            'error' => ['bg'=>'#fef2f2','border'=>'#fecaca','dot'=>'#dc2626','text'=>'#991b1b','label'=>'Critical'],
        ];
        $c = $colors[$status] ?? $colors['warn'];
    @endphp
    <div class="service-card" data-service="{{ $svc['key'] }}"
         style="background:#fff;border:1.5px solid {{ $c['border'] }};border-radius:12px;padding:18px 18px 14px;background:{{ $c['bg'] }}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:18px;line-height:1">{{ $svc['icon'] }}</span>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#0B2447">{{ $svc['name'] }}</div>
                    <div style="font-size:11px;color:#64748b;margin-top:1px">{!! $svc['sub'] !!}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;background:#fff;border:1px solid {{ $c['border'] }}">
                <div style="width:7px;height:7px;border-radius:50%;background:{{ $c['dot'] }};{{ $status==='ok' ? 'animation:pulse 2s ease-in-out infinite' : '' }}"></div>
                <span style="font-size:11px;font-weight:700;color:{{ $c['text'] }}">{{ $c['label'] }}</span>
            </div>
        </div>
        <div style="font-size:12px;color:#334155;line-height:1.5" data-msg="{{ $svc['key'] }}">
            {{ $d['message'] ?? '—' }}
        </div>
        @if(!empty($d['latency_ms']))
        <div style="margin-top:6px">
            @php $latMs = $d['latency_ms']; $latW = min(100, ($latMs / 2000) * 100); @endphp
            <div style="height:3px;background:#e2e8f0;border-radius:2px;overflow:hidden">
                <div style="height:100%;width:{{ $latW }}%;background:{{ $c['dot'] }};border-radius:2px;transition:width .4s"></div>
            </div>
        </div>
        @endif
        @if(isset($d['used_pct']))
        <div style="margin-top:6px">
            <div style="height:3px;background:#e2e8f0;border-radius:2px;overflow:hidden">
                <div style="height:100%;width:{{ $d['used_pct'] }}%;background:{{ $c['dot'] }};border-radius:2px"></div>
            </div>
        </div>
        @endif
        @if($svc['key'] === 'system' && isset($d['load_pct']))
        <div style="margin-top:8px;display:flex;gap:12px">
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:{{ $c['text'] }}">{{ $d['load_pct'] ?? 0 }}%</div>
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em">CPU</div>
            </div>
            @if(!empty($d['mem_used_pct']))
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:#334155">{{ $d['mem_used_pct'] }}%</div>
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em">RAM</div>
            </div>
            @endif
            @if(!empty($d['uptime']))
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:#334155">{{ $d['uptime'] }}</div>
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Uptime</div>
            </div>
            @endif
        </div>
        @endif
        @if($svc['key'] === 'ssl' && isset($d['days_left']))
        <div style="margin-top:8px">
            <div style="font-size:22px;font-weight:800;color:{{ $c['text'] }}">{{ $d['days_left'] }} days</div>
            <div style="font-size:11px;color:#64748b">until certificate expiry</div>
        </div>
        @endif
        @if($svc['key'] === 'queue')
        <div style="margin-top:8px;display:flex;gap:12px">
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:#334155">{{ $d['pending'] ?? 0 }}</div>
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Pending</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:16px;font-weight:800;color:{{ ($d['failed']??0) > 0 ? '#b45309' : '#334155' }}">{{ $d['failed'] ?? 0 }}</div>
                <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Failed</div>
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- ── Backup / Sentry notes ────────────────────────────────────────────── --}}
<div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 18px">
        <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px">🗄️ Database Backups</div>
        <div style="font-size:12px;color:#374151">Managed daily by Render.com PostgreSQL. Verify retention policy in the Render dashboard → Database → Backups. Run a restore test quarterly.</div>
    </div>
    @if(!config('sentry.dsn'))
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px 18px">
        <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px">⚡ Sentry Not Active</div>
        <div style="font-size:12px;color:#374151">Run <code style="background:#fff;padding:1px 5px;border-radius:4px;font-size:11px">composer require sentry/sentry-laravel</code> on Render Shell, then set <code style="background:#fff;padding:1px 5px;border-radius:4px;font-size:11px">SENTRY_LARAVEL_DSN</code> to enable real-time alerting.</div>
    </div>
    @else
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 18px">
        <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px">✅ Sentry Active</div>
        <div style="font-size:12px;color:#374151">Real-time error monitoring and alerting is configured. Exceptions, JS errors, queue failures, and scheduled task failures are reported to Sentry.</div>
    </div>
    @endif
</div>

</div>

<style>
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;text-align:center}
.stat-num{font-size:28px;font-weight:800;line-height:1}
.stat-lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
@keyframes spin{to{transform:rotate(360deg)}}
.spinning{animation:spin .7s linear infinite}
</style>

<script>
var HEALTH_URL = '{{ route("ceo.health.data") }}';
var refreshInterval = null;

function formatTime(iso) {
    var d = new Date(iso);
    return 'Updated ' + d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}

function statusColors(status) {
    if (status === 'ok')    return {bg:'#f0fdf4',border:'#bbf7d0',dot:'#16a34a',text:'#166534',label:'Healthy'};
    if (status === 'error') return {bg:'#fef2f2',border:'#fecaca',dot:'#dc2626',text:'#991b1b',label:'Critical'};
    return {bg:'#fffbeb',border:'#fde68a',dot:'#d97706',text:'#92400e',label:'Warning'};
}

function applyData(data) {
    // Update timestamp
    var lbl = document.getElementById('last-updated');
    if (lbl && data.checked_at) lbl.textContent = formatTime(data.checked_at);

    // Update stats strip
    var statuses = ['database','queue','ai','paystack','mail','storage','system','ssl','users','errors'].map(function(k){ return (data[k]||{}).status; });
    var ok    = statuses.filter(function(s){ return s==='ok'; }).length;
    var warn  = statuses.filter(function(s){ return s==='warn'; }).length;
    var error = statuses.filter(function(s){ return s==='error'; }).length;
    setStat('ok-count',    ok);
    setStat('warn-count',  warn);
    setStat('error-count', error);
    setStat('active-users',  (data.users||{}).active_15min  || 0);
    setStat('online-riders', (data.users||{}).online_riders || 0);
    setStat('errors-24h',    (data.errors||{}).count_24h    || 0);
    setStat('queue-failed',  (data.queue||{}).failed        || 0);

    // Update each service card message
    ['database','queue','ai','paystack','mail','storage','system','ssl','users','errors'].forEach(function(key) {
        var d   = data[key] || {};
        var msg = document.querySelector('[data-msg="'+key+'"]');
        if (msg) msg.textContent = d.message || '—';

        // Update border/bg colour of the whole card
        var card = document.querySelector('[data-service="'+key+'"]');
        if (card) {
            var c = statusColors(d.status);
            card.style.border = '1.5px solid ' + c.border;
            card.style.background = c.bg;
        }
    });
}

function setStat(attr, value) {
    var el = document.querySelector('[data-stat="'+attr+'"] .stat-num');
    if (el) el.textContent = value;
}

function setRefreshSpinner(active) {
    var icon = document.getElementById('refresh-icon');
    if (icon) icon.classList.toggle('spinning', active);
}

function refreshNow() {
    setRefreshSpinner(true);
    fetch(HEALTH_URL, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(data){ applyData(data); setRefreshSpinner(false); })
        .catch(function(){ setRefreshSpinner(false); });
}

// Set initial timestamp
(function(){
    var checkedAt = @json($health['checked_at'] ?? null);
    if (checkedAt) {
        var lbl = document.getElementById('last-updated');
        if (lbl) lbl.textContent = formatTime(checkedAt);
    }
    // Auto-refresh every 30 s
    refreshInterval = setInterval(refreshNow, 30000);
})();
</script>
</x-app-layout>
