<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Changelog — MSAS FarmAI</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#0f172a;font-size:15px;line-height:1.7}
.nav{background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{font-weight:800;font-size:17px;color:#0F6B3E;text-decoration:none}
.nav-links{display:flex;gap:20px;font-size:13px}
.nav-links a{color:#475569;text-decoration:none;font-weight:500}
.nav-links a:hover{color:#0F6B3E}
.hero{background:linear-gradient(135deg,#0B2447 0%,#0d4a2e 60%,#0F6B3E 100%);color:#fff;padding:52px 24px 44px;text-align:center}
.hero h1{font-size:28px;font-weight:800;margin-bottom:8px}
.hero p{font-size:14px;opacity:.8}
.container{max-width:760px;margin:0 auto;padding:48px 24px 80px}
footer{text-align:center;padding:24px;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0}
@media(max-width:600px){.nav-links{display:none}}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ url('/') }}" class="nav-brand">MSAS FarmAI</a>
    <div class="nav-links">
        <a href="{{ route('legal.terms') }}">Terms</a>
        <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
        <a href="{{ route('login') }}">Log in</a>
    </div>
</nav>

<div class="hero">
    <h1>Platform Changelog</h1>
    <p>What's new on MSAS FarmAI</p>
</div>

<div class="container">
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

<footer>
    &copy; {{ date('Y') }} MSAS Livestock & Agro Services. All rights reserved. &nbsp;·&nbsp;
    <a href="{{ route('legal.privacy') }}" style="color:#64748b">Privacy Policy</a>
</footer>

</body>
</html>
