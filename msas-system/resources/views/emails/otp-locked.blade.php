<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verification Blocked</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

      {{-- Header --}}
      <tr>
        <td style="background:#0F6B3E;padding:28px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.7);">MSAS FarmAI</p>
          <h1 style="margin:8px 0 0;font-size:20px;font-weight:800;color:#ffffff;">Security Alert</h1>
        </td>
      </tr>

      {{-- Body --}}
      <tr>
        <td style="padding:36px 40px;">
          <p style="margin:0 0 16px;font-size:15px;color:#334155;">Hi {{ $user->first_name }},</p>

          <p style="margin:0 0 20px;font-size:15px;color:#475569;line-height:1.6;">
            Your account verification was temporarily blocked after <strong>5 failed code attempts</strong>.
            This is an automated protection to prevent unauthorised access.
          </p>

          {{-- Alert box --}}
          <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
            <tr>
              <td style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:20px 24px;">
                <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.5px;">Blocked attempt details</p>
                <table cellpadding="0" cellspacing="0" style="margin-top:10px;">
                  <tr>
                    <td style="font-size:13px;color:#64748b;padding:3px 16px 3px 0;white-space:nowrap;">IP Address</td>
                    <td style="font-size:13px;font-weight:600;color:#0f172a;font-family:monospace;">{{ $ipAddress }}</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;color:#64748b;padding:3px 16px 3px 0;white-space:nowrap;">Time</td>
                    <td style="font-size:13px;font-weight:600;color:#0f172a;">{{ $lockedAt }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

          <p style="margin:0 0 20px;font-size:15px;color:#475569;line-height:1.6;">
            <strong>If this was you</strong>, simply request a new verification code from the login page and try again.
          </p>

          <p style="margin:0 0 28px;font-size:15px;color:#475569;line-height:1.6;">
            <strong>If this wasn't you</strong>, your account may be under a targeted attack.
            We recommend changing your password immediately.
          </p>

          <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
            <tr>
              <td style="background:#0F6B3E;border-radius:8px;">
                <a href="{{ url('/forgot-password') }}"
                   style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                  Reset My Password
                </a>
              </td>
            </tr>
          </table>

          <p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
            If you believe this is an error or need help, contact our support team.
            Do not share your verification codes with anyone — MSAS will never ask for them.
          </p>
        </td>
      </tr>

      {{-- Footer --}}
      <tr>
        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#94a3b8;">
            © {{ date('Y') }} MSAS FarmAI · This is an automated security notification.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
