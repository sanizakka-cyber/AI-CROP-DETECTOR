<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.header{background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:32px 32px 24px;text-align:center;}
.logo-circle{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;}
.header h1{color:#fff;font-size:22px;font-weight:800;margin:0;letter-spacing:-.3px;}
.header p{color:rgba(255,255,255,.7);font-size:13px;margin:6px 0 0;}
.body{padding:32px;}
.greeting{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:8px;}
.text{font-size:14px;color:#475569;line-height:1.7;margin-bottom:16px;}
.badge{display:inline-block;background:#f0fdf4;border:1px solid #bbf7d0;color:#0F6B3E;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em;}
.steps{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;margin:20px 0;}
.step{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;}
.step:last-child{margin-bottom:0;}
.step-num{width:24px;height:24px;border-radius:50%;background:#0F6B3E;color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.step-text{font-size:13px;color:#374151;}
.step-text strong{color:#0f172a;}
.info-box{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin:16px 0;}
.info-box p{font-size:13px;color:#92400e;margin:0;line-height:1.6;}
.footer{background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #e2e8f0;}
.footer p{font-size:12px;color:#94a3b8;margin:0;}
.footer a{color:#0F6B3E;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="logo-circle">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                <path d="M16 3C9.373 3 4 8.373 4 15c0 4.418 2.239 8.309 5.636 10.6L9 29h14l-.636-3.4C25.761 23.309 28 19.418 28 15c0-6.627-5.373-12-12-12z" fill="white" fill-opacity="0.9"/>
                <path d="M13 15l2 2 5-5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>MSAS FarmAI</h1>
        <p>Livestock &amp; Agro Services Platform</p>
    </div>

    <div class="body">
        <p class="greeting">Hello {{ $user->first_name }},</p>
        <p class="text">
            Thank you for applying to join the <strong>MSAS FarmAI</strong> platform as a
            <span class="badge">{{ $user->roleLabel }}</span>.
        </p>
        <p class="text">
            Your application has been received and is currently under review by our administration team.
        </p>

        <div class="steps">
            <div class="step">
                <div class="step-num">✓</div>
                <div class="step-text"><strong>Application Submitted</strong><br>Your registration and documents have been received.</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text"><strong>Document Review</strong><br>Our team will verify your credentials and qualifications.</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text"><strong>Account Activation</strong><br>Once approved, you'll receive an email with your login details.</div>
            </div>
        </div>

        <div class="info-box">
            <p>⏱ Review typically takes <strong>1–3 business days</strong>. We may contact you if additional information is needed.</p>
        </div>

        <p class="text">If you have any questions, please contact us at <a href="mailto:{{ config('mail.from.address') }}" style="color:#0F6B3E;font-weight:700;">{{ config('mail.from.address') }}</a>.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} MSAS — Livestock &amp; Agro Services. All rights reserved.</p>
        <p style="margin-top:4px;"><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
    </div>
</div>
</body></html>
