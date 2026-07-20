<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;}
.wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.header{background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:32px 32px 24px;text-align:center;}
.logo-circle{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.15);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;}
.header h1{color:#fff;font-size:22px;font-weight:800;margin:0;}
.header p{color:rgba(255,255,255,.7);font-size:13px;margin:6px 0 0;}
.body{padding:32px;}
.success-badge{text-align:center;margin-bottom:20px;}
.success-icon{width:64px;height:64px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;}
.success-title{font-size:20px;font-weight:800;color:#0F6B3E;margin:0;}
.text{font-size:14px;color:#475569;line-height:1.7;margin-bottom:16px;}
.cta-btn{display:block;text-align:center;background:linear-gradient(135deg,#0F6B3E,#1FA84A);color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:12px;margin:24px 0;}
.details{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 20px;margin:16px 0;}
.details p{font-size:13px;color:#15803d;margin:0 0 6px;line-height:1.6;}
.details p:last-child{margin:0;}
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
        <div class="success-badge">
            <div class="success-icon">
                <svg width="32" height="32" fill="none" stroke="#0F6B3E" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="success-title">Application Approved! 🎉</p>
        </div>

        <p class="text">
            Dear <strong>{{ $user->first_name }}</strong>, congratulations! Your application to join MSAS FarmAI
            as a <strong>{{ $user->roleLabel }}</strong> has been <strong style="color:#0F6B3E;">approved</strong>.
        </p>

        <p class="text">Your account is now active. You can log in immediately using the email address and password you registered with.</p>

        <a href="{{ route('login') }}" class="cta-btn">Log In to MSAS FarmAI →</a>

        <div class="details">
            <p>✅ <strong>Account Status:</strong> Active</p>
            <p>👤 <strong>Role:</strong> {{ $user->roleLabel }}</p>
            <p>📧 <strong>Login Email:</strong> {{ $user->email }}</p>
        </div>

        <p class="text">
            If you have any questions or need assistance getting started, our support team is available at
            <a href="mailto:{{ config('mail.from.address') }}" style="color:#0F6B3E;font-weight:700;">{{ config('mail.from.address') }}</a>.
        </p>

        <p class="text">Welcome to the MSAS FarmAI community!</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} MSAS — Livestock &amp; Agro Services. All rights reserved.</p>
        <p style="margin-top:4px;"><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
    </div>
</div>
</body></html>
