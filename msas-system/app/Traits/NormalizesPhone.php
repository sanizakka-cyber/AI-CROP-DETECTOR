<?php

namespace App\Traits;

trait NormalizesPhone
{
    /**
     * Returns true if the input looks like a phone number (Nigerian or international).
     */
    protected function looksLikePhone(string $input): bool
    {
        $clean = preg_replace('/[\s\-\(\)\.]/', '', $input);

        // Nigerian local: 0[789]XXXXXXXXX (11 digits)
        if (preg_match('/^0[789]\d{9}$/', $clean)) return true;

        // Nigerian with country code without +: 234[789]XXXXXXXXX
        if (preg_match('/^234[789]\d{9}$/', $clean)) return true;

        // International E.164 with +: +[1-9][6-14 more digits]
        if (preg_match('/^\+[1-9]\d{6,14}$/', $clean)) return true;

        return false;
    }

    /**
     * Normalise any recognised phone format to E.164 (+country digits).
     */
    protected function normalizePhone(string $phone): string
    {
        $hasPlus = str_starts_with(ltrim($phone), '+');
        $digits  = preg_replace('/\D/', '', $phone);

        // Nigerian local 0XXXXXXXXXX → +234XXXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234' . substr($digits, 1);
        }

        // Already 234XXXXXXXXXX (13 digits)
        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+' . $digits;
        }

        // Had an explicit + sign → trust the caller's country code
        if ($hasPlus) {
            return '+' . $digits;
        }

        // Bare 10-digit number starting with 7/8/9 → assume Nigerian 234
        if (strlen($digits) === 10 && preg_match('/^[789]/', $digits)) {
            return '+234' . $digits;
        }

        return '+' . ltrim($digits, '0');
    }

    /**
     * Mask a phone for display: +234 801*** ***345 → +234 80*******345
     */
    protected function maskPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) < 7) return $phone;
        return '+' . substr($clean, 0, 3) . str_repeat('*', strlen($clean) - 6) . substr($clean, -3);
    }
}
