<?php

namespace App\Support;

/**
 * The one severity value for a scan (Phase 12.2).
 *
 * Before this, the API's `aiResult.severity` was filled from urgency_level,
 * so the app's header badge said "HIGH" (urgency) while the details said
 * "Moderate" (severity_level) for the same scan. Severity and urgency are
 * different questions: how bad the condition is, and how soon to act.
 *
 * diagnoses.severity_level is now the only stored severity. It is
 * normalised here on every write (Diagnosis::setSeverityLevelAttribute), and
 * every reader (web history, PDF, API, app, narration) displays that column
 * or its lowercase key(). Urgency is shown separately, always labelled as urgency.
 */
final class Severity
{
    /** Canonical labels, mildest first: the engine's schema enum, title-cased. */
    public const LEVELS = ['None', 'Mild', 'Moderate', 'Severe', 'Critical'];

    private const SYNONYMS = [
        'none' => 'None', 'healthy' => 'None', 'no' => 'None',
        'mild' => 'Mild', 'low' => 'Mild', 'minor' => 'Mild', 'slight' => 'Mild',
        'moderate' => 'Moderate', 'medium' => 'Moderate',
        'severe' => 'Severe', 'high' => 'Severe', 'serious' => 'Severe',
        'critical' => 'Critical', 'very severe' => 'Critical', 'emergency' => 'Critical',
    ];

    /** Any stored or engine value -> a canonical label, or null when absent/unknown. Never guesses a default. */
    public static function normalize(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $k = strtolower(trim(preg_replace('/\s+/', ' ', $raw)));
        if ($k === '' || in_array($k, ['not_applicable', 'n/a', 'na', 'unknown', 'null'], true)) {
            return null;
        }
        return self::SYNONYMS[$k] ?? null;
    }

    /** Lowercase key for clients: none|mild|moderate|severe|critical, or null. */
    public static function key(?string $level): ?string
    {
        $n = self::normalize($level);
        return $n === null ? null : strtolower($n);
    }

    /**
     * The severity the model itself wrote, read back from raw_model_output
     * (structured JSON, or a legacy "severity | X" line). Used to recompute
     * stored rows. Returns false when the raw output says nothing about
     * severity, so the caller can tell "absent" from "said none".
     */
    public static function fromRawModelOutput(?string $raw): string|null|false
    {
        if (!is_string($raw) || trim($raw) === '') {
            return false;
        }
        // Structured reply; raw_model_output is capped at 4000 chars, so a long
        // reply may be cut off: match the key instead of decoding the JSON.
        if (preg_match('/"severity"\s*:\s*(null|"([^"]*)")/i', $raw, $m)) {
            return isset($m[2]) ? self::normalize($m[2]) : null;
        }
        if (preg_match('/^\s*severity\s*\|\s*(.+?)\s*$/im', $raw, $m)) {
            return self::normalize($m[1]);
        }
        return false;
    }
}
