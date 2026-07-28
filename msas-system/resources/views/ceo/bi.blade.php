<x-app-layout>
<x-slot name="header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Business Intelligence</h2>
            <p style="font-size:12px;color:#64748b;margin:3px 0 0;">Insights from the last 90 days · {{ now()->format('M d, Y') }}</p>
        </div>
        <a href="{{ route('ceo.monitoring') }}" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">← System Monitor</a>
    </div>
</x-slot>

<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:1280px;margin:0 auto;padding:0 20px;">

{{-- ── Subscription Funnel ─────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:20px;">Subscription Conversion Funnel</div>
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
    @php
    $funnelSteps = [
        ['label'=>'Registered Farmers','value'=>$totalFarmers,'color'=>'#0F6B3E','bg'=>'#f0fdf4'],
        ['label'=>'Ever Subscribed','value'=>$hadAnySub,'color'=>'#0369a1','bg'=>'#eff6ff'],
        ['label'=>'Currently Active','value'=>$activeSub,'color'=>'#7c3aed','bg'=>'#f5f3ff'],
        ['label'=>'Paying (non-trial)','value'=>$paidSub,'color'=>'#b45309','bg'=>'#fffbeb'],
    ];
    @endphp
    @foreach($funnelSteps as $i => $step)
    <div style="flex:1;min-width:140px;background:{{ $step['bg'] }};border-radius:14px;padding:18px 20px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:{{ $step['color'] }};">{{ number_format($step['value']) }}</div>
        <div style="font-size:11px;font-weight:700;color:#64748b;margin-top:4px;text-transform:uppercase;letter-spacing:0.05em;">{{ $step['label'] }}</div>
        @if($i > 0 && $funnelSteps[$i-1]['value'] > 0)
        <div style="font-size:12px;color:{{ $step['color'] }};font-weight:800;margin-top:6px;">
            {{ round($step['value'] / $funnelSteps[$i-1]['value'] * 100, 1) }}%
        </div>
        @endif
    </div>
    @if($i < count($funnelSteps) - 1)
    <div style="font-size:20px;color:#cbd5e1;flex-shrink:0;">→</div>
    @endif
    @endforeach
    </div>
</div>

{{-- ── Two column: Revenue chart + Plan distribution ───────────────────────── --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

    {{-- Revenue trend --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;">Monthly Revenue — Last 12 Months</div>
            <div style="font-size:12px;font-weight:800;color:#0F6B3E;">₦{{ number_format($revenueByMonth->sum()) }}</div>
        </div>
        <canvas id="revenueChart" height="120" style="width:100%;display:block;"></canvas>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:9px;color:#94a3b8;">
            <span>{{ now()->subMonths(11)->format('M Y') }}</span>
            <span>{{ now()->format('M Y') }}</span>
        </div>
    </div>

    {{-- Plan distribution --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">Active Plan Mix</div>
        @php $planColors = ['basic'=>'#0F6B3E','pro'=>'#0369a1','enterprise'=>'#7c3aed','trial'=>'#b45309']; $planTotal = $planDist->sum('count') ?: 1; @endphp
        @forelse($planDist as $plan)
        @php $pct = round($plan->count / $planTotal * 100); $col = $planColors[$plan->plan] ?? '#94a3b8'; @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;">
                <span style="text-transform:capitalize;">{{ $plan->plan }}</span>
                <span>{{ $plan->count }} ({{ $pct }}%)</span>
            </div>
            <div style="background:#f1f5f9;border-radius:4px;height:8px;">
                <div style="width:{{ $pct }}%;background:{{ $col }};border-radius:4px;height:100%;transition:width 0.5s;"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0;">No active subscriptions yet.</p>
        @endforelse
    </div>

</div>

{{-- ── Disease Heatmap by State ────────────────────────────────────────────── --}}
@if(!empty($heatmap))
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
        <div>
            <div style="font-size:13px;font-weight:700;color:#0f172a;">Disease Heatmap by State</div>
            <div style="font-size:11px;color:#64748b;margin-top:2px;">Top 10 states × top 5 diseases — last 90 days</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:10px;color:#94a3b8;">
            <div style="width:10px;height:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:2px;"></div>low
            <div style="width:10px;height:10px;background:#4ade80;border-radius:2px;"></div>medium
            <div style="width:10px;height:10px;background:#0F6B3E;border-radius:2px;"></div>high
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr>
                <th style="text-align:left;padding:8px 12px;font-weight:700;color:#64748b;white-space:nowrap;">State</th>
                @foreach($topDiseases as $disease)
                <th style="text-align:center;padding:8px 10px;font-weight:700;color:#64748b;white-space:nowrap;max-width:120px;">{{ \Illuminate\Support\Str::limit($disease, 18) }}</th>
                @endforeach
                <th style="text-align:center;padding:8px 10px;font-weight:700;color:#64748b;">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($topStates as $state)
        @php $rowTotal = array_sum($heatmap[$state] ?? []); @endphp
        <tr style="border-top:1px solid #f1f5f9;">
            <td style="padding:8px 12px;font-weight:700;color:#0f172a;white-space:nowrap;">{{ $state }}</td>
            @foreach($topDiseases as $disease)
            @php
                $v = $heatmap[$state][$disease] ?? 0;
                $intensity = $heatmapMax > 0 ? $v / $heatmapMax : 0;
                if ($intensity === 0) { $bg = '#f8fafc'; $fg = '#cbd5e1'; }
                elseif ($intensity < 0.25) { $bg = '#dcfce7'; $fg = '#166534'; }
                elseif ($intensity < 0.6) { $bg = '#4ade80'; $fg = '#14532d'; }
                else { $bg = '#0F6B3E'; $fg = '#fff'; }
            @endphp
            <td style="text-align:center;padding:8px 10px;">
                <span style="display:inline-block;min-width:28px;padding:3px 8px;border-radius:6px;background:{{ $bg }};color:{{ $fg }};font-weight:{{ $v>0?'800':'400' }};">{{ $v ?: '–' }}</span>
            </td>
            @endforeach
            <td style="text-align:center;padding:8px 10px;font-weight:700;color:#0F6B3E;">{{ $rowTotal }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- ── Two column: Top diseases + Top experts ──────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

    {{-- Top Diseases --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">Top Diseases — Last 30 Days</div>
        @php $maxDisease = $topDiseasesData->max('count') ?: 1; @endphp
        @forelse($topDiseasesData as $d)
        @php $pct = round($d->count / $maxDisease * 100); @endphp
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#475569;margin-bottom:3px;">
                <span>{{ \Illuminate\Support\Str::limit($d->disease_name, 30) }}</span>
                <span style="color:#0F6B3E;">{{ $d->count }}</span>
            </div>
            <div style="background:#f1f5f9;border-radius:4px;height:7px;">
                <div style="width:{{ $pct }}%;background:linear-gradient(90deg,#1FA84A,#0F6B3E);border-radius:4px;height:100%;"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0;">No data in the last 30 days.</p>
        @endforelse
    </div>

    {{-- Top Experts --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">Top Performing Experts</div>
        @forelse($topExperts as $i => $expert)
        <div style="display:flex;align-items:center;gap:12px;padding:8px 0;{{ $i < $topExperts->count()-1 ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
            <div style="width:28px;height:28px;border-radius:50%;background:{{ $i===0?'#fef9c3':($i===1?'#e2e8f0':($i===2?'#fef3e2':'#f1f5f9')) }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:{{ $i===0?'#b45309':($i===1?'#475569':($i===2?'#92400e':'#94a3b8')) }};flex-shrink:0;">{{ $i+1 }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $expert->first_name }} {{ $expert->last_name }}</div>
                <div style="font-size:10px;color:#94a3b8;text-transform:capitalize;">{{ str_replace('-',' ',$expert->role) }} · {{ $expert->state }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:14px;font-weight:900;color:#0F6B3E;">{{ $expert->consult_count }}</div>
                @if($expert->avg_rating)
                <div style="font-size:10px;color:#b45309;">★ {{ number_format($expert->avg_rating,1) }}</div>
                @endif
            </div>
        </div>
        @empty
        <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0;">No expert consultations yet.</p>
        @endforelse
    </div>

</div>

{{-- ── Scan types + Crop types + Users trend ───────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">

    {{-- Scan Type Breakdown --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">AI Scan Types</div>
        <div style="font-size:10px;color:#64748b;margin-bottom:12px;">{{ number_format($totalScans) }} total · {{ $avgScansPerUser }} avg per farmer</div>
        @php $scanColors = ['plant'=>'#0F6B3E','animal'=>'#0369a1']; @endphp
        @foreach($scanTypes as $type => $count)
        @php $pct = $totalScans > 0 ? round($count / $totalScans * 100) : 0; $col = $scanColors[$type] ?? '#94a3b8'; @endphp
        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:5px;">
                <span style="color:#475569;text-transform:capitalize;">🌿 {{ $type }}</span>
                <span style="color:{{ $col }};">{{ $pct }}%</span>
            </div>
            <div style="background:#f1f5f9;border-radius:6px;height:12px;">
                <div style="width:{{ $pct }}%;background:{{ $col }};border-radius:6px;height:100%;"></div>
            </div>
            <div style="font-size:10px;color:#94a3b8;margin-top:3px;">{{ number_format($count) }} scans</div>
        </div>
        @endforeach
    </div>

    {{-- Crop Types from Consultations --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">Top Crops in Consultations</div>
        @php $maxCrop = $cropTypes->max('count') ?: 1; @endphp
        @forelse($cropTypes as $crop)
        @php $pct = round($crop->count / $maxCrop * 100); @endphp
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <div style="font-size:11px;font-weight:700;color:#475569;width:90px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $crop->crop_type }}</div>
            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:7px;">
                <div style="width:{{ $pct }}%;background:#1FA84A;border-radius:4px;height:100%;"></div>
            </div>
            <div style="font-size:11px;font-weight:700;color:#0F6B3E;width:28px;text-align:right;flex-shrink:0;">{{ $crop->count }}</div>
        </div>
        @empty
        <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0;">No crop consultation data.</p>
        @endforelse
    </div>

    {{-- Monthly new users --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">New Users — Last 6 Months</div>
        <canvas id="usersChart" height="120" style="width:100%;display:block;"></canvas>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:9px;color:#94a3b8;">
            <span>{{ now()->subMonths(5)->format('M') }}</span>
            <span>{{ now()->format('M') }}</span>
        </div>
    </div>

</div>

</div>
</div>

<script>
// Revenue bar chart
(function() {
    const canvas = document.getElementById('revenueChart');
    if (!canvas) return;
    canvas.width = canvas.offsetWidth * window.devicePixelRatio;
    canvas.height = 120 * window.devicePixelRatio;
    canvas.style.height = '120px';
    const ctx = canvas.getContext('2d');
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

    const data = @json($revenueByMonth->values());
    const labels = @json($months12->map(fn($m) => \Carbon\Carbon::parse($m)->format('M'))->values());
    const max = Math.max(...data, 1);
    const W = canvas.offsetWidth, H = 120;
    const barW = (W - 40) / data.length - 4;
    const padL = 20, padB = 20, chartH = H - padB - 8;

    ctx.clearRect(0, 0, W, H);

    // Grid line
    ctx.strokeStyle = '#f1f5f9'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(padL, 8); ctx.lineTo(W, 8); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(padL, chartH/2+8); ctx.lineTo(W, chartH/2+8); ctx.stroke();

    data.forEach((v, i) => {
        const x = padL + i * (barW + 4);
        const h = Math.max(3, (v / max) * chartH);
        const y = 8 + chartH - h;
        const grad = ctx.createLinearGradient(0, y, 0, y + h);
        grad.addColorStop(0, '#1FA84A');
        grad.addColorStop(1, '#0F6B3E');
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.roundRect(x, y, barW, h, [3, 3, 0, 0]);
        ctx.fill();
        // Label
        ctx.fillStyle = '#94a3b8'; ctx.font = '8px system-ui'; ctx.textAlign = 'center';
        ctx.fillText(labels[i], x + barW/2, H - 4);
    });
})();

// Users bar chart
(function() {
    const canvas = document.getElementById('usersChart');
    if (!canvas) return;
    canvas.width = canvas.offsetWidth * window.devicePixelRatio;
    canvas.height = 120 * window.devicePixelRatio;
    canvas.style.height = '120px';
    const ctx = canvas.getContext('2d');
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

    const data = @json($usersByMonth->values());
    const labels = @json($months6->map(fn($m) => \Carbon\Carbon::parse($m)->format('M'))->values());
    const max = Math.max(...data, 1);
    const W = canvas.offsetWidth, H = 120;
    const barW = (W - 20) / data.length - 4;
    const padL = 10, padB = 20, chartH = H - padB - 8;

    ctx.clearRect(0, 0, W, H);
    data.forEach((v, i) => {
        const x = padL + i * (barW + 4);
        const h = Math.max(3, (v / max) * chartH);
        const y = 8 + chartH - h;
        const grad = ctx.createLinearGradient(0, y, 0, y+h);
        grad.addColorStop(0, '#60a5fa');
        grad.addColorStop(1, '#1d4ed8');
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.roundRect(x, y, barW, h, [3,3,0,0]);
        ctx.fill();
        ctx.fillStyle = '#94a3b8'; ctx.font = '8px system-ui'; ctx.textAlign = 'center';
        ctx.fillText(labels[i], x + barW/2, H-4);
    });
})();
</script>
</x-app-layout>