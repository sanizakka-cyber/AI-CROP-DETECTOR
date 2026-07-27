<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MSAS FarmAI Security Code</title>
<style>
body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
.wrap{max-width:520px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
.header{background:linear-gradient(135deg,#0B2447,#0F6B3E);padding:32px;text-align:center;}
.logo{font-size:28px;font-weight:900;color:#fff;letter-spacing:-0.5px;}
.logo span{color:#F4A300;}
.body{padding:32px;}
h2{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 8px;}
p{color:#475569;font-size:15px;line-height:1.6;margin:0 0 20px;}
.code-box{background:#f8fafc;border:2px dashed #0F6B3E;border-radius:12px;padding:24px;text-align:center;margin:24px 0;}
.code{font-size:40px;font-weight:900;color:#0F6B3E;letter-spacing:8px;font-family:'Courier New',monospace;}
.expiry{font-size:13px;color:#94a3b8;margin-top:8px;}
.footer{padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8;}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="logo">MSAS <span>Farm</span>AI</div>
    </div>
    <div class="body">
        <h2>Your Security Code</h2>
        <p>Hello {{ $user->first_name }},</p>
        <p>Someone attempted to sign in to your MSAS FarmAI account. Use the code below to complete your login. This code expires in <strong>10 minutes</strong>.</p>
        <div class="code-box">
            <div class="code">{{ $code }}</div>
            <div class="expiry">Expires in 10 minutes</div>
        </div>
        <p>If you did not attempt to log in, please change your password immediately and contact support.</p>
        <p style="color:#94a3b8;font-size:13px;">Never share this code with anyone — MSAS staff will never ask for it.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} MSAS FarmAI · <a href="https://ai.msasagro.com" style="color:#0F6B3E;">ai.msasagro.com</a>
    </div>
</div>
</body>
</html>
