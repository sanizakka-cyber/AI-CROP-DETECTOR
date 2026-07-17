<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const TTL_MINUTES  = 5;
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate a new OTP for the given identifier and type,
     * replacing any existing unverified OTP for the same pair.
     */
    public function generate(string $identifier, string $type): string
    {
        // Delete previous OTPs for same identifier+type
        Otp::where('identifier', $identifier)->where('type', $type)->delete();

        $plain = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::create([
            'identifier' => $identifier,
            'type'       => $type,
            'code'       => Hash::make($plain),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $plain;
    }

    /**
     * Verify the supplied plain-text code.
     * Returns true on success; throws a descriptive exception on failure.
     */
    public function verify(string $identifier, string $type, string $plain): true
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw new \RuntimeException('No verification code found. Please request a new code.');
        }

        if ($otp->tooManyAttempts()) {
            throw new \RuntimeException('Too many incorrect attempts. Please request a new verification code.');
        }

        if ($otp->isExpired()) {
            throw new \RuntimeException('Verification code has expired. Request another code.');
        }

        if (! Hash::check($plain, $otp->code)) {
            $otp->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $otp->fresh()->attempts;
            throw new \RuntimeException(
                $remaining > 0
                    ? "Verification code is incorrect. {$remaining} attempt(s) remaining."
                    : 'Too many incorrect attempts. Please request a new verification code.'
            );
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Send OTP via email.
     */
    public function sendViaEmail(string $email, string $plain, string $firstName = 'User'): void
    {
        Mail::send([], [], function ($message) use ($email, $plain, $firstName) {
            $message->to($email)
                ->subject('Verify your MSAS Account')
                ->html($this->emailBody($firstName, $plain));
        });
    }

    /**
     * Send OTP via SMS using the configured driver.
     */
    public function sendViaSms(string $phone, string $plain): void
    {
        $message = "Your MSAS verification code is {$plain}. Expires in " . self::TTL_MINUTES . " minutes. Do not share this code.";
        app(SmsService::class)->send($phone, $message);
    }

    private function emailBody(string $name, string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 0;">
  <tr><td align="center">
    <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.07);overflow:hidden;">
      <tr><td style="background:linear-gradient(135deg,#0F6B3E,#1FA84A);padding:28px 36px;">
        <h1 style="color:#fff;font-size:20px;margin:0;font-weight:800;">MSAS FarmAI</h1>
        <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px;">Livestock &amp; Agro Services</p>
      </td></tr>
      <tr><td style="padding:36px;">
        <p style="margin:0 0 8px;font-size:15px;color:#374151;">Hello <strong>{$name}</strong>,</p>
        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">Welcome to MSAS Livestock &amp; Agro Services. Use the verification code below to confirm your account:</p>
        <div style="text-align:center;margin:28px 0;">
          <div style="display:inline-block;background:#f0fdf4;border:2px dashed #16a34a;border-radius:12px;padding:20px 36px;">
            <span style="font-size:36px;font-weight:900;letter-spacing:12px;color:#0F6B3E;font-family:'Courier New',monospace;">{$code}</span>
          </div>
        </div>
        <p style="margin:0 0 8px;font-size:13px;color:#6b7280;text-align:center;">This code expires in <strong>5 minutes</strong>.</p>
        <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;text-align:center;">If you did not create this account, ignore this email.</p>
      </td></tr>
      <tr><td style="background:#f8fafc;padding:16px 36px;border-top:1px solid #f1f5f9;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">© 2026 MSAS Livestock &amp; Agro Services &bull; msasagro.com</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
