<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Welcome to MSAS FarmAI</title>
<style>
  body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
  .wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:40px 36px 32px;text-align:center;}
  .logo{font-size:28px;font-weight:900;color:#fff;letter-spacing:-0.5px;margin-bottom:6px;}
  .logo span{color:#F4A300;}
  .header-sub{color:rgba(255,255,255,0.7);font-size:14px;}
  .body{padding:36px 36px 24px;}
  .hi{font-size:22px;font-weight:800;color:#0f172a;margin:0 0 10px;}
  p{font-size:14px;color:#475569;line-height:1.75;margin:0 0 14px;}
  .steps{margin:24px 0;display:flex;flex-direction:column;gap:12px;}
  .step{display:flex;align-items:flex-start;gap:14px;background:#f8fafc;border-radius:12px;padding:14px 16px;border:1px solid #e2e8f0;}
  .step-num{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .step-text strong{display:block;font-size:14px;font-weight:700;color:#0f172a;margin-bottom:2px;}
  .step-text span{font-size:12px;color:#64748b;}
  .cta{display:block;background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff!important;text-decoration:none;font-weight:800;font-size:15px;padding:15px 28px;border-radius:12px;text-align:center;margin:28px 0 16px;}
  .cta-alt{display:block;background:#f0fdf4;border:2px solid #0F6B3E;color:#0F6B3E!important;text-decoration:none;font-weight:700;font-size:14px;padding:12px 28px;border-radius:12px;text-align:center;margin-bottom:24px;}
  .tip{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;font-size:13px;color:#92400e;margin:20px 0;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 36px;text-align:center;}
  .footer p{font-size:12px;color:#94a3b8;margin:0;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="logo">MSAS <span>FarmAI</span></div>
    <div class="header-sub">Modern Smart Agriculture System</div>
  </div>
  <div class="body">
    <h1 class="hi">Welcome, {{ $user->first_name }}!</h1>
    <p>Your account is verified and ready. MSAS FarmAI puts AI-powered crop and livestock diagnostics, expert consultations, and a full farm management suite right in your hands.</p>

    <p style="font-weight:700;color:#0f172a;margin-bottom:8px;">Here's how to get started in 4 steps:</p>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-text">
          <strong>Complete your profile</strong>
          <span>Add your farm location, farm name, and contact details.</span>
        </div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-text">
          <strong>Run your first AI scan</strong>
          <span>Take a photo of a sick crop or animal — get a diagnosis in seconds.</span>
        </div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-text">
          <strong>Record your livestock or crops</strong>
          <span>Track your animals, poultry flocks, or planted crops in one place.</span>
        </div>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div class="step-text">
          <strong>Start your free 14-day trial</strong>
          <span>Unlock vet consultations, detailed reports, and unlimited AI scans.</span>
        </div>
      </div>
    </div>

    <a href="{{ config('app.url') }}" class="cta">Open MSAS FarmAI Now →</a>
    <a href="{{ config('app.url') }}/subscription/plans" class="cta-alt">Start Free 14-Day Trial</a>

    <div class="tip">
      <strong>Tip:</strong> The AI scan works best in good lighting. Hold your phone 20–30 cm from the affected area and keep it steady before taking the photo.
    </div>

    <p>If you have any questions or need help, reply to this email or use the in-app support chat anytime.</p>
    <p style="color:#0f172a;font-weight:700;">Welcome to the future of farming.</p>
  </div>
  <div class="footer">
    <p>MSAS FarmAI · Modern Smart Agriculture System · Nigeria</p>
    <p style="margin-top:6px;">You received this because you created an account at <a href="{{ config('app.url') }}" style="color:#0F6B3E;">ai.msasagro.com</a></p>
  </div>
</div>
</body>
</html>