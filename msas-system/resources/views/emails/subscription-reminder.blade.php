<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Plan is Expiring Soon</title>
<style>
  body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
  .wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0F6B3E,#1FA84A);padding:32px 36px 24px;text-align:center;}
  .header h1{color:#fff;font-size:22px;font-weight:800;margin:0;}
  .header p{color:#bbf7d0;font-size:14px;margin:6px 0 0;}
  .body{padding:32px 36px;}
  .countdown{background:#fef9c3;border:2px solid #fef08a;border-radius:12px;padding:16px;text-align:center;margin-bottom:24px;}
  .countdown .days{font-size:48px;font-weight:900;color:#92400e;line-height:1;}
  .countdown .label{font-size:13px;color:#78350f;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;}
  .plan-badge{display:inline-block;background:#f0fdf4;border:1px solid #bbf7d0;color:#0F6B3E;border-radius:20px;padding:4px 16px;font-size:13px;font-weight:700;margin-bottom:16px;}
  h2{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 10px;}
  p{font-size:14px;color:#475569;line-height:1.7;margin:0 0 14px;}
  .cta{display:block;background:#0F6B3E;color:#fff!important;text-decoration:none;font-weight:700;font-size:15px;padding:14px 28px;border-radius:10px;text-align:center;margin:24px 0;}
  .cta-alt{display:block;background:#f0fdf4;border:2px solid #0F6B3E;color:#0F6B3E!important;text-decoration:none;font-weight:700;font-size:14px;padding:12px 28px;border-radius:10px;text-align:center;margin-bottom:24px;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 36px;text-align:center;}
  .footer p{font-size:12px;color:#94a3b8;margin:0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Renew Before You Lose Access</h1>
    <p>Your plan is expiring soon</p>
  </div>
  <div class="body">

    <div class="countdown">
      <div class="days">{{ $daysLeft }}</div>
      <div class="label">{{ $daysLeft === 1 ? 'Day Left' : 'Days Left' }}</div>
    </div>

    <span class="plan-badge">{{ config("subscription.plans.{$subscription->plan}.name", ucfirst($subscription->plan)) }}</span>
    <h2>Hi {{ $user->first_name }}, your plan expires {{ $daysLeft === 1 ? 'tomorrow' : "in {$daysLeft} days" }}.</h2>
    <p>Your <strong>{{ config("subscription.plans.{$subscription->plan}.name", ucfirst($subscription->plan)) }}</strong> subscription ends on <strong>{{ $subscription->ends_at?->format('F d, Y \a\t g:i A') }}</strong>. Renew now to keep uninterrupted access to all your AI tools, scans, and expert consultations.</p>

    <a href="{{ route('subscription.plans') }}" class="cta">Renew My Plan Now →</a>

    @php
    $plans = config('subscription.plans');
    $currentLevel = $plans[$subscription->plan]['plan_level'] ?? 0;
    $nextPlan = collect($plans)->first(fn($p) => ($p['plan_level'] ?? 0) === $currentLevel + 1);
    @endphp

    @if($nextPlan)
    <a href="{{ route('subscription.plans') }}" class="cta-alt">Or Upgrade to {{ $nextPlan['name'] }} →</a>
    @endif

    <p style="font-size:13px;color:#94a3b8;">If you have already renewed, please ignore this email. Questions? Reply here or contact support.</p>
  </div>
  <div class="footer">
    <p>MSAS FarmAI · Nigeria's Smart Agricultural Platform<br>
    You received this because you have an active subscription at msas.ng</p>
  </div>
</div>
</body>
</html>
