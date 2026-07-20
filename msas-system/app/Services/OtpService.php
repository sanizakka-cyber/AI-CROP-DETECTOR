<?php

namespace App\Services;

use App\Mail\OtpMail;
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
        // Guard: catch misconfigured mail settings before attempting send
        $username = config('mail.mailers.smtp.username', '');
        if (empty($username) || str_contains(strtolower($username), 'your_gmail') || str_contains($username, 'YOUR_GMAIL')) {
            Log::error('OTP email not sent: MAIL_USERNAME is not configured in .env', [
                'email_hint' => $this->hint($email),
            ]);
            OtpDeliveryLog::record(
                userId:         $userId ?? User::where('email', $email)->value('id'),
                identifierHint: $this->hint($email),
                type:           $otpType,
                channel:        'email',
                delivered:      false,
                provider:       'smtp',
                error:          'MAIL_USERNAME not configured — placeholder value detected in .env',
            );
            return false;
        }

        try {
            Mail::to($email)->send(new OtpMail($firstName, $plain, self::TTL_MINUTES, $otpType));

            Log::info('OTP email sent', ['email_hint' => $this->hint($email), 'type' => $otpType]);

            OtpDeliveryLog::record(
                userId:         $userId ?? User::where('email', $email)->value('id'),
                identifierHint: $this->hint($email),
                type:           $otpType,
                channel:        'email',
                delivered:      true,
                provider:       config('mail.mailers.' . config('mail.default') . '.transport', 'smtp'),
            );

            return true;

        } catch (\Throwable $e) {
            Log::error('OTP email send failed', [
                'email_hint' => $this->hint($email),
                'error'      => $e->getMessage(),
                'mailer'     => config('mail.default'),
                'host'       => config('mail.mailers.smtp.host'),
                'scheme'     => config('mail.mailers.smtp.scheme') ?: '(empty — correct for port 587)',
            ]);

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
}
