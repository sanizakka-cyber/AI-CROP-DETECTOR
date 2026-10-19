<?php

namespace App\Support;

/**
 * Masks secrets in an error message before it is stored, logged or shown to
 * staff: API keys (Anthropic, Paystack, generic), bearer tokens, Laravel
 * APP_KEYs, key=value pairs that name a secret, credentials in URLs and long
 * opaque tokens. The farmer never sees these messages at all.
 */
final class SecretMasker
{
    private const PATTERNS = [
        '/sk-ant-[A-Za-z0-9_\-]{8,}/'                                  => 'sk-ant-***',
        '/\b(sk|pk)_(live|test)_[A-Za-z0-9]{6,}/'                       => '$1_$2_***',
        '/\bBearer\s+[A-Za-z0-9._\-+\/=]{6,}/i'                         => 'Bearer ***',
        '/base64:[A-Za-z0-9+\/]{20,}={0,2}/'                            => 'base64:***',
        '/((?:api[_-]?key|x-api-key|key|token|secret|password|passwd|authorization)["\']?\s*[:=]\s*["\']?)[^\s"\'&,}]{4,}/i' => '$1***',
        '/(https?:\/\/)[^\s\/:@]+:[^\s\/@]+@/i'                         => '$1***:***@',
        '/\b[A-Za-z0-9_\-]{40,}\b/'                                    => '***',
    ];

    public static function mask(?string $text, int $max = 2000): ?string
    {
        if ($text === null) {
            return null;
        }
        $out = $text;
        foreach (self::PATTERNS as $re => $rep) {
            $out = preg_replace($re, $rep, $out) ?? $out;
        }
        // Also mask the configured secrets themselves, wherever they appear.
        foreach ([config('services.ai_engine.key'), config('app.key'), config('services.paystack.secret_key')] as $secret) {
            if (is_string($secret) && strlen($secret) >= 8) {
                $out = str_replace($secret, '***', $out);
            }
        }
        return mb_strlen($out) > $max ? mb_substr($out, 0, $max) . '…' : $out;
    }
}
