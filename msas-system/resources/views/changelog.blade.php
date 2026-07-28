<x-app-layout>
<x-slot name="header">
    <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">Platform Changelog</h2>
</x-slot>
<div style="padding:24px 0 60px;background:#f1f5f9;min-height:100vh;">
<div style="max-width:760px;margin:0 auto;padding:0 20px;">
@php
$releases = [
    ['version'=>'v1.3 — Phase 10','date'=>'2026-07-28','label'=>'new','items'=>[
        'Weekly CEO digest email with user, revenue, and scan metrics',
        'System health scorecard with automated alerts',
        'NPS survey modal shown after 30 days of activity',
    ]],
    ['version'=>'v1.2 — Phase 8-9','date'=>'2026-07-28','label'=>'new','items'=>[
        'Referral system: unique codes, shareable links, leaderboard',
        'NPS (Net Promoter Score) collection and CEO dashboard',
        'Compliance: NDPR data export, account deletion request',
        'Audit log viewer for CEO and Admin',
    ]],
    ['version'=>'v1.1 — Phase 4-7','date'=>'2026-07-28','label'=>'new','items'=>[
        'Business Intelligence dashboard: disease heatmap, revenue trends, subscription funnel',
        'Support ticket system (farmer submission + CEO/admin management)',
        'In-app notification centre',
        'CEO broadcast tool to push notifications to user segments',
        'Database backup scheduled daily + system health command',
    ]],
    ['version'=>'v1.0 — Production Launch','date'=>'2026-07-24','label'=>'stable','items'=>[
        'Live ops monitoring dashboard with 60-second auto-refresh',
        'Pilot program: invite codes, pilot badge, onboarding checklist',
        'Feedback widget (floating modal, 5-star rating, AJAX submit)',
        'Vet/agronomist consultation API with Jitsi video rooms',
        'Rider order management API',
        'Wallet balance, history, and withdrawal request API',
        'Knowledge base public API',
        'Order tracking public API (no auth required)',
        'Welcome email for new farmers',
        'CEO staff management with voice engine and locale support',
    ]],
];
@endphp

@foreach($releases as $r)
<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:24px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <div style="font-size:15px;font-weight:800;color:#0f172a;">{{ $r['version'] }}</div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:{{ $r['label']==='new'?'#dbeafe':'#dcfce7' }};color:{{ $r['label']==='new'?'#1d4ed8':'#166534' }};text-transform:uppercase;">{{ $r['label'] }}</span>
        <div style="font-size:11px;color:#94a3b8;margin-left:auto;">{{ $r['date'] }}</div>
    </div>
    <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;">
    @foreach($r['items'] as $item)
    <li style="font-size:13px;color:#475569;line-height:1.5;">{{ $item }}</li>
    @endforeach
    </ul>
</div>
@endforeach

</div>
</div>
</x-app-layout>