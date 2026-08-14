<?php

namespace App\Services;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpLockedException;
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
    public const TTL_MINUTES  = 10;
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

        // Check expiry before attempts — an expired code should always say "expired",
        // even if the user also exceeded the attempt limit.
        if ($otp->isExpired()) {
            Log::info('OTP verify: expired', ['hint' => $this->hint($identifier), 'type' => $type, 'expired_at' => $otp->expires_at]);
            throw new OtpExpiredException('Verification code has expired. Request another code.');
        }

        if ($otp->tooManyAttempts()) {
            Log::warning('OTP verify: too many attempts', ['hint' => $this->hint($identifier), 'type' => $type]);
            throw new OtpLockedException('Too many incorrect attempts. Please request a new verification code.');
        }

        if (! Hash::check($plain, $otp->code)) {
            $otp->increment('attempts');
            $remaining = max(0, self::MAX_ATTEMPTS - $otp->fresh()->attempts);
            Log::info('OTP verify: wrong code', ['hint' => $this->hint($identifier), 'type' => $type, 'attempts_left' => $remaining]);

            if ($remaining === 0) {
                throw new OtpLockedException('Too many incorrect attempts. Please request a new verification code.');
            }

            throw new OtpInvalidException(
                "Verification code is incorrect. {$remaining} attempt(s) remaining.",
                $remaining,
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
    public function sendViaEmail(string $email, string $plain, string $firstName = 'User', ?int $userId = null, string $otpType = 'registration', ?string $verifyUrl = null): bool
    {
        // Guard: catch unconfigured mail host (placeholder value) before attempting send.
        // Note: username check removed — API-key mailers (Resend, Postmark, SES) have no username.
        $host = config('mail.mailers.smtp.host', '');
        if (empty($host) || $host === '127.0.0.1') {
            Log::error('OTP email not sent: MAIL_HOST is not configured for production', [
                'email_hint' => $this->hint($email),
                'host'       => $host ?: '(empty)',
            ]);
            OtpDeliveryLog::record(
                userId:         $userId ?? User::where('email', $email)->value('id'),
                identifierHint: $this->hint($email),
                type:           $otpType,
                channel:        'email',
                delivered:      false,
                provider:       'smtp',
                error:          'MAIL_HOST not configured for production — localhost/empty detected',
            );
            return false;
        }

        try {
            Mail::to($email)->send(new OtpMail($firstName, $plain, self::TTL_MINUTES, $otpType, $verifyUrl));

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
