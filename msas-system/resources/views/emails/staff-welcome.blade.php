<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $isReset ? 'Password Reset' : 'Welcome to MSAS FarmAI' }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; }
  .wrap { max-width:560px; margin:32px auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
  .header { background:#0F3460; padding:28px 32px; text-align:center; }
  .header img { height:36px; }
  .header h1 { color:#ffffff; font-size:20px; font-weight:700; margin:12px 0 0; }
  .header p { color:#94a3b8; font-size:13px; margin:4px 0 0; }
  .body { padding:32px; }
  .greeting { font-size:15px; color:#1e293b; margin:0 0 16px; font-weight:600; }
  .text { font-size:14px; color:#475569; line-height:1.7; margin:0 0 20px; }
  .cred-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px 24px; margin:24px 0; }
  .cred-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e2e8f0; }
  .cred-row:last-child { border-bottom:none; padding-bottom:0; }
  .cred-label { font-size:12px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
  .cred-value { font-size:14px; color:#1e293b; font-weight:700; font-family:monospace; }
  .notice { background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:12px 16px; font-size:13px; color:#92400e; margin:0 0 24px; }
  .btn { display:inline-block; background:#0F6B3E; color:#ffffff; text-decoration:none; font-size:14px; font-weight:700; padding:13px 28px; border-radius:10px; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:20px 32px; text-align:center; }
  .footer p { font-size:12px; color:#94a3b8; margin:0; }
  .security-note { font-size:12px; color:#ef4444; margin:20px 0 0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>MSAS FarmAI</h1>
    <p>{{ $isReset ? 'Password Reset Notification' : 'Staff Account Created' }}</p>
  </div>
  <div class="body">
    <p class="greeting">Hello, {{ $staff->first_name }}!</p>

    @if($isReset)
    <p class="text">
      Your MSAS FarmAI staff account password has been reset by an administrator.
      Use the temporary credentials below to log in. You will be required to choose a new password immediately after logging in.
    </p>
    @else
    <p class="text">
      Welcome to the MSAS FarmAI platform! A staff account has been created for you.
      Use the credentials below to access the system. For security, you must set a new password the first time you log in.
    </p>
    @endif

    <div class="cred-box">
      <div class="cred-row">
        <span class="cred-label">Login URL</span>
        <span class="cred-value">{{ config('app.url') }}/login</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value">{{ $staff->email }}</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Temporary Password</span>
        <span class="cred-value">{{ $temporaryPassword }}</span>
      </div>
      @if($staff->role)
      <div class="cred-row">
        <span class="cred-label">Role</span>
        <span class="cred-value">{{ ucwords(str_replace('-', ' ', $staff->role)) }}</span>
      </div>
      @endif
    </div>

    <div class="notice">
      ⚠ This is a temporary password. You <strong>must</strong> change it immediately upon first login.
      Do not share these credentials with anyone.
    </div>

    <a href="{{ config('app.url') }}/login" class="btn">Log In Now →</a>

    <p class="security-note">
      If you did not expect this email, please contact your administrator immediately at
      <a href="mailto:{{ config('mail.from.address') }}" style="color:#ef4444;">{{ config('mail.from.address') }}</a>.
    </p>
  </div>
  <div class="footer">
    <p>MSAS FarmAI Platform &nbsp;·&nbsp; This is an automated message, please do not reply.</p>
    <p style="margin-top:6px;">© {{ date('Y') }} MSAS Agro. All rights reserved.</p>
  </div>
</div>
</body>
</html>
