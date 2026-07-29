<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>New sign-in detected — MSAS FarmAI</title>
<style>
body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
.wrap{max-width:520px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
.header{background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:32px;text-align:center;}
.logo{font-size:28px;font-weight:900;color:#fff;letter-spacing:-0.5px;}
.logo span{color:#F4A300;}
.body{padding:32px;}
h2{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 8px;}
p{color:#475569;font-size:15px;line-height:1.6;margin:0 0 16px;}
.detail-box{background:#f8fafc;border-left:4px solid #0F6B3E;border-radius:8px;padding:16px 20px;margin:20px 0;}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #e2e8f0;}
.detail-row:last-child{border-bottom:none;}
.detail-label{font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;}
.detail-value{font-size:14px;color:#334155;font-weight:600;}
.alert{background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:14px 18px;margin:20px 0;font-size:14px;color:#92400e;line-height:1.6;}
.btn{display:inline-block;background:#0F6B3E;color:#fff;font-weight:700;font-size:14px;padding:12px 28px;border-radius:10px;text-decoration:none;margin-top:4px;}
.footer{padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8;line-height:1.7;}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="logo">MSAS <span>Farm</span>AI</div>
    </div>
    <div class="body">
        <h2>New Sign-In Detected</h2>
        <p>Hello {{ $user->first_name }},</p>
        <p>We detected a sign-in to your MSAS FarmAI account from a new device or browser. Here are the details:</p>

        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label">Browser</span>
                <span class="detail-value">{{ $browser }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Platform</span>
                <span class="detail-value">{{ $platform }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">IP Address</span>
                <span class="detail-value">{{ $ipAddress }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value">{{ $loginTime }}</span>
            </div>
        </div>

        <div class="alert">
            <strong>Was this you?</strong> If you recognise this sign-in, you can safely ignore this email.
            If this was not you, change your password immediately and review your trusted devices.
        </div>

        <a href="{{ route('profile.security', [], true) }}" class="btn">Review Account Security</a>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} MSAS FarmAI &middot;
        <a href="{{ url('/') }}" style="color:#0F6B3E;">msasagro.com</a><br>
        This is an automated security notification. Do not reply to this email.
    </div>
</div>
</body>
</html>
