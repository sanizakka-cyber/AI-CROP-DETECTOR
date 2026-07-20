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
.text{font-size:14px;color:#475569;line-height:1.7;margin-bottom:16px;}
.reason-box{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px 20px;margin:16px 0;}
.reason-box p{font-size:13px;color:#991b1b;margin:0;line-height:1.7;}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin:16px 0;}
.info-box p{font-size:13px;color:#1e40af;margin:0;line-height:1.6;}
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
            </svg>
        </div>
        <h1>MSAS FarmAI</h1>
        <p>Livestock &amp; Agro Services Platform</p>
    </div>

    <div class="body">
        <p class="text">Dear <strong>{{ $user->first_name }}</strong>,</p>

        <p class="text">
            Thank you for your interest in joining MSAS FarmAI as a <strong>{{ $user->roleLabel }}</strong>.
            After reviewing your application, we are unable to approve your request at this time.
        </p>

        @if($reason)
        <div class="reason-box">
            <p><strong>Reason for this decision:</strong><br>{{ $reason }}</p>
        </div>
        @endif

        <div class="info-box">
            <p>
                📩 If you believe this is an error or you have additional documentation to provide,
                please contact us at
                <a href="mailto:{{ config('mail.from.address') }}" style="color:#1d4ed8;font-weight:700;">{{ config('mail.from.address') }}</a>
                and we will be happy to assist you.
            </p>
        </div>

        <p class="text">You are welcome to submit a new application once you have addressed the points above.</p>

        <p class="text">Thank you for your understanding.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} MSAS — Livestock &amp; Agro Services. All rights reserved.</p>
        <p style="margin-top:4px;"><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
    </div>
</div>
</body></html>
