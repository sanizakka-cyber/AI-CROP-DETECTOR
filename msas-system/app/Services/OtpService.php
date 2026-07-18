<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\OtpDeliveryLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const TTL_MINUTES  = 5;
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate a fresh OTP (replacing any previous one for the same pair).
     * Wrapped in a transaction to prevent duplicate rows from concurrent requests.
     * Returns the plain 6-digit code.
     */
    public function generate(string $identifier, string $type): string
    {
        $plain = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($identifier, $type, $plain) {
            Otp::where('identifier', $identifier)->where('type', $type)->delete();

            Otp::create([
                'identifier' => $identifier,
                'type'       => $type,
                'code'       => Hash::make($plain),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });

        Log::info('OTP generated', [
            'identifier_hint' => $this->hint($identifier),
            'type'            => $type,
            'expires_at'      => now()->addMinutes(self::TTL_MINUTES)->toISOString(),
        ]);

        return $plain;
    }

    /**
     * Return the expiry timestamp for the most recent OTP of this pair,
     * so the view can anchor the countdown to the real server TTL.
     */
    public function expiresAt(string $identifier, string $type): ?\Carbon\Carbon
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        return $otp?->expires_at;
    }

    /**
     * Verify the supplied plain-text code.
     * Returns true on success; throws a descriptive RuntimeException on failure.
     */
    public function verify(string $identifier, string $type, string $plain): true
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            Log::warning('OTP verify: no pending OTP found', ['hint' => $this->hint($identifier), 'type' => $type]);
            throw new \RuntimeException('No verification code found. Please request a new code.');
        }

        if ($otp->tooManyAttempts()) {
            Log::warning('OTP verify: too many attempts', ['hint' => $this->hint($identifier), 'type' => $type]);
            throw new \RuntimeException('Too many incorrect attempts. Please request a new verification code.');
        }

        if ($otp->isExpired()) {
            Log::info('OTP verify: expired', ['hint' => $this->hint($identifier), 'type' => $type, 'expired_at' => $otp->expires_at]);
            throw new \RuntimeException('Verification code has expired. Request another code.');
        }

        if (! Hash::check($plain, $otp->code)) {
            $otp->increment('attempts');
            $remaining = max(0, self::MAX_ATTEMPTS - $otp->fresh()->attempts);
            Log::info('OTP verify: wrong code', ['hint' => $this->hint($identifier), 'type' => $type, 'attempts_left' => $remaining]);

            throw new \RuntimeException(
                $remaining > 0
                    ? "Verification code is incorrect. {$remaining} attempt(s) remaining."
                    : 'Too many incorrect attempts. Please request a new verification code.'
            );
        }

        $otp->update(['verified_at' => now()]);

        Log::info('OTP verified successfully', ['hint' => $this->hint($identifier), 'type' => $type]);

        return true;
    }

    /**
     * Send OTP via email.
     * Returns true on success, false on failure (logged internally).
     */
    public function sendViaEmail(string $email, string $plain, string $firstName = 'User', ?int $userId = null, string $otpType = 'registration'): bool
    {
        try {
            Mail::send([], [], function ($message) use ($email, $plain, $firstName) {
                $message->to($email)
                    ->subject('Verify your MSAS Account')
                    ->html($this->emailBody($firstName, $plain));
            });

            Log::info('OTP email sent', ['email_hint' => $this->hint($email)]);

            OtpDeliveryLog::record(
                userId:         $userId ?? User::where('email', $email)->value('id'),
                identifierHint: $this->hint($email),
                type:           $otpType,
                channel:        'email',
                delivered:      true,
                provider:       'smtp',
            );

            return true;

        } catch (\Throwable $e) {
            Log::error('OTP email send failed', ['email_hint' => $this->hint($email), 'error' => $e->getMessage()]);

            OtpDeliveryLog::record(
                userId:         $userId ?? User::where('email', $email)->value('id'),
                identifierHint: $this->hint($email),
                type:           $otpType,
                channel:        'email',
                delivered:      false,
                provider:       'smtp',
                error:          $e->getMessage(),
            );

            return false;
        }
    }

    /**
     * Send OTP via SMS.
     * Returns true on success, false on failure (logged by SmsService).
     */
    public function sendViaSms(string $phone, string $plain, ?int $userId = null, string $otpType = 'registration'): bool
    {
        $message = "Your MSAS verification code is {$plain}. It expires in "
            . self::TTL_MINUTES . " minutes. Do not share this code with anyone.";

        $result = app(SmsService::class)->send($phone, $message);

        OtpDeliveryLog::record(
            userId:         $userId ?? User::where('phone', $phone)->value('id'),
            identifierHint: $this->hint($phone),
            type:           $otpType,
            channel:        'sms',
            delivered:      $result['success'],
            provider:       $result['provider'],
            messageId:      $result['message_id'],
            error:          $result['error'],
        );

        if (! $result['success']) {
            Log::warning('OTP SMS delivery failed', [
                'phone_hint' => $this->hint($phone),
                'provider'   => $result['provider'],
                'error'      => $result['error'],
            ]);
        }

        return $result['success'];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** Redact middle characters for safe logging. */
    private function hint(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            return substr($local, 0, 2) . '***@' . $domain;
        }
        $clean = preg_replace('/\D/', '', $identifier);
        return substr($clean, 0, 3) . '***' . substr($clean, -3);
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
        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">Use the verification code below to confirm your account:</p>
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
