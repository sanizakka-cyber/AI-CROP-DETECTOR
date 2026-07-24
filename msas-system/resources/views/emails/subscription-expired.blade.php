<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Plan Has Expired</title>
<style>
  body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
  .wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);}
  .header{background:#dc2626;padding:32px 36px 24px;text-align:center;}
  .header img{height:40px;margin-bottom:12px;}
  .header h1{color:#fff;font-size:22px;font-weight:800;margin:0;}
  .header p{color:#fecaca;font-size:14px;margin:6px 0 0;}
  .body{padding:32px 36px;}
  .plan-badge{display:inline-block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:20px;padding:4px 16px;font-size:13px;font-weight:700;margin-bottom:20px;}
  h2{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 10px;}
  p{font-size:14px;color:#475569;line-height:1.7;margin:0 0 14px;}
  .cta{display:block;background:#0F6B3E;color:#fff!important;text-decoration:none;font-weight:700;font-size:15px;padding:14px 28px;border-radius:10px;text-align:center;margin:24px 0;}
  .info-box{background:#fef9c3;border:1px solid #fef08a;border-radius:10px;padding:14px 16px;font-size:13px;color:#713f12;margin-bottom:20px;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 36px;text-align:center;}
  .footer p{font-size:12px;color:#94a3b8;margin:0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Plan Expired</h1>
    <p>Your MSAS FarmAI subscription has ended</p>
  </div>
  <div class="body">
    <span class="plan-badge">{{ config("subscription.plans.{$subscription->plan}.name", ucfirst($subscription->plan)) }}</span>
    <h2>Hi {{ $user->first_name }}, your plan has expired.</h2>
    <p>Your <strong>{{ config("subscription.plans.{$subscription->plan}.name", ucfirst($subscription->plan)) }}</strong> subscription expired on <strong>{{ $subscription->ends_at?->format('F d, Y') }}</strong>. Your farm data is safe, but access to premium features has been paused.</p>

    <div class="info-box">
      <strong>What you lose without an active plan:</strong><br>
      AI Smart Scans, AI diagnostics, expert consultations, advanced reports, and marketplace selling access.
    </div>

    <p>Renew now to restore full access instantly — your data is waiting for you.</p>
    <a href="{{ route('subscription.plans') }}" class="cta">Renew My Plan →</a>

    <p style="font-size:13px;color:#94a3b8;">If you have questions, reply to this email or contact our support team. We're here to help.</p>
  </div>
  <div class="footer">
    <p>MSAS FarmAI · Nigeria's Smart Agricultural Platform<br>
    You received this because you have an account at msas.ng</p>
  </div>
</div>
</body>
</html>
