<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $code,
        public readonly int    $ttlMinutes,
        public readonly string $otpType = 'registration',
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->otpType) {
            'password_reset' => 'Reset your MSAS password',
            default          => 'Verify your MSAS account',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody());
    }

    private function htmlBody(): string
    {
        $code       = htmlspecialchars($this->code, ENT_QUOTES);
        $name       = htmlspecialchars($this->firstName, ENT_QUOTES);
        $ttl        = $this->ttlMinutes;
        $actionLine = $this->otpType === 'password_reset'
            ? 'Use the code below to reset your MSAS password:'
            : 'Use the verification code below to confirm your account:';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 0;">
  <tr><td align="center">
    <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.07);overflow:hidden;max-width:480px;">
      <tr><td style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);padding:28px 36px;">
        <h1 style="color:#fff;font-size:20px;margin:0;font-weight:800;">MSAS FarmAI</h1>
        <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px;">Livestock &amp; Agro Services</p>
      </td></tr>
      <tr><td style="padding:36px;">
        <p style="margin:0 0 8px;font-size:15px;color:#374151;">Hello <strong>{$name}</strong>,</p>
        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">{$actionLine}</p>
        <div style="text-align:center;margin:28px 0;">
          <div style="display:inline-block;background:#f0fdf4;border:2px dashed #16a34a;border-radius:12px;padding:20px 36px;">
            <span style="font-size:36px;font-weight:900;letter-spacing:12px;color:#0F6B3E;font-family:'Courier New',monospace;">{$code}</span>
          </div>
        </div>
        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-align:center;">
          This code expires in <strong>{$ttl} minutes</strong>.
        </p>
        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-align:center;">
          Do not share this code with anyone.
        </p>
        <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;text-align:center;">
          If you did not request this, you can safely ignore this email.
        </p>
      </td></tr>
      <tr><td style="background:#f8fafc;padding:16px 36px;border-top:1px solid #f1f5f9;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">
          &copy; 2026 MSAS Livestock &amp; Agro Services &bull; msas.online
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
