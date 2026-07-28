<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">NPS Dashboard</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:900px;margin:0 auto;padding:0 20px;">

    @php
    $npsColor = is_null($nps) ? '#94a3b8' : ($nps >= 50 ? '#0F6B3E' : ($nps >= 0 ? '#b45309' : '#dc2626'));
    @endphp

    {{-- Big NPS score --}}
    <div style="background:#fff;border-radius:18px;border:1px solid #e2e8f0;padding:32px;text-align:center;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;margin-bottom:8px;">Net Promoter Score</div>
        <div style="font-size:72px;font-weight:900;color:{{ $npsColor }};line-height:1;">{{ is_null($nps) ? '—' : ($nps > 0 ? '+'.$nps : $nps) }}</div>
        <div style="font-size:13px;color:#64748b;margin-top:8px;">Based on {{ $total }} response{{ $total===1?'':'s' }} · Avg score: {{ $avg ?? '—' }}/10</div>
        @if(!is_null($nps))
        <div style="display:flex;justify-content:center;gap:24px;margin-top:20px;">
            <div style="text-align:center;"><div style="font-size:22px;font-weight:900;color:#0F6B3E;">{{ $promoters }}</div><div style="font-size:10px;color:#64748b;text-transform:uppercase;">Promoters (9-10)</div></div>
            <div style="text-align:center;"><div style="font-size:22px;font-weight:900;color:#94a3b8;">{{ $passives }}</div><div style="font-size:10px;color:#64748b;text-transform:uppercase;">Passives (7-8)</div></div>
            <div style="text-align:center;"><div style="font-size:22px;font-weight:900;color:#dc2626;">{{ $detractors }}</div><div style="font-size:10px;color:#64748b;text-transform:uppercase;">Detractors (0-6)</div></div>
        </div>
        @endif
    </div>

    {{-- Score distribution --}}
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:16px;">Score Distribution</div>
        @php $maxDist = max(1, max($dist)); @endphp
        <div style="display:flex;align-items:flex-end;gap:4px;height:100px;">
        @for($i=0;$i<=10;$i++)
        @php
            $v = $dist[$i];
            $h = max(2, round($v/$maxDist*90));
            $col = $i>=9?'#0F6B3E':($i>=7?'#94a3b8':'#dc2626');
        @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;" title="Score {{ $i }}: {{ $v }} responses">
            <div style="font-size:9px;color:#94a3b8;margin-bottom:2px;">{{ $v }}</div>
            <div style="width:100%;height:{{ $h }}px;background:{{ $col }};border-radius:3px 3px 0 0;"></div>
            <div style="font-size:9px;color:#475569;font-weight:700;margin-top:2px;">{{ $i }}</div>
        </div>
        @endfor
        </div>
    </div>
</div>
</div>
</x-app-layout>