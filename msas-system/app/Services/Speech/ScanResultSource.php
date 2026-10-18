<?php

namespace App\Services\Speech;

use App\Models\Diagnosis;
use App\Services\DiagnosisResultMapper;

/**
 * The source document for "Listen to your result": a stored scan result
 * reduced to {observations[], explanation, recommendations[], safety_note}.
 *
 * Built only from what the scan already says (no new advice, no invented
 * values). Rejected or inconclusive scans say so and carry the photo tips.
 */
class ScanResultSource
{
    private const NOT_A_FINDING = ['', 'none', 'none detected', 'no pest detected', 'n/a', 'not applicable', 'unknown'];

    public static function fromDiagnosis(Diagnosis $d): array
    {
        return self::fromAttributes($d->getAttributes() + [
            'image_issues' => $d->image_issues, 'photo_tips' => $d->photo_tips,
        ]);
    }

    /**
     * @return array{content: array, source_lang: string, protected_terms: string[]}
     */
    public static function fromAttributes(array $a): array
    {
        $outcome = $a['assessment_outcome'] ?? null;
        $text = fn ($k) => trim((string) ($a[$k] ?? ''));

        if (in_array($outcome, ['poor_image', 'unsupported_subject', 'uncertain', 'provider_error'], true)) {
            $observations = self::list($a['image_issues'] ?? []);
            $explanation = self::sentence(DiagnosisResultMapper::outcomeHeadline($outcome)) . ' ' . self::sentence($text('explanation'));
            $recommendations = array_merge(self::list($a['photo_tips'] ?? []), self::list($text('vet_referral_advice')));
        } else {
            $observations = [];
            if ($text('subject_name') !== '') {
                $observations[] = self::sentence('Subject: ' . $text('subject_name') . ($text('detected_part') !== '' ? ' (' . $text('detected_part') . ')' : ''));
            }
            if ($text('health_status') !== '') {
                $observations[] = self::sentence('Health status: ' . $text('health_status'));
            }
            foreach (self::list($text('symptoms_identified')) as $s) {
                $observations[] = $s;
            }
            if ($text('severity_level') !== '') {
                $observations[] = self::sentence('Severity: ' . $text('severity_level'));
            }

            $explanation = '';
            if (!self::isNothing($text('disease_name'))) {
                $explanation = self::sentence('Likely condition: ' . $text('disease_name'));
                if (isset($a['confidence_score']) && $a['confidence_score'] !== null && $a['confidence_score'] !== '') {
                    $explanation .= ' ' . self::sentence('The AI is ' . (int) round((float) $a['confidence_score']) . '% sure of this');
                }
            }
            $explanation = trim($explanation . ' ' . self::sentence($text('explanation') !== '' ? $text('explanation') : $text('cause')));

            $recommendations = [];
            foreach (['first_aid_steps', 'recommended_medication', 'fertilizer_recommendation', 'preventive_measures', 'vet_referral_advice'] as $k) {
                if (!self::isNothing($text($k))) {
                    $recommendations = array_merge($recommendations, self::list($text($k)));
                }
            }
        }

        return [
            'content' => [
                'observations'    => array_values($observations),
                'explanation'     => trim($explanation),
                'recommendations' => array_values($recommendations),
                'safety_note'     => config('speech.safety_note_en', 'This is AI guidance and may be wrong. Confirm with an extension officer or vet before using chemicals or medicines.'),
            ],
            'source_lang'     => ($a['language'] ?? null) ?: 'en',
            'protected_terms' => self::protectedTerms($a),
        ];
    }

    /** Product and scientific names that must survive translation unchanged. */
    public static function protectedTerms(array $a): array
    {
        $terms = [];
        $sci = trim((string) ($a['scientific_name'] ?? ''));
        if ($sci !== '' && !self::isNothing($sci)) {
            $terms[] = $sci;
        }
        foreach (['recommended_medication', 'fertilizer_recommendation'] as $k) {
            foreach (self::list((string) ($a[$k] ?? '')) as $item) {
                // Capitalised words that do not start a sentence: product / active-ingredient names.
                preg_match_all('/(?<![.!?:]\s)(?<!^)\b(\p{Lu}[\p{L}-]+(?:\s+\p{Lu}[\p{L}-]+)*)/u', $item, $m);
                foreach ($m[1] as $name) {
                    if (mb_strlen($name) >= 3) {
                        $terms[] = $name;
                    }
                }
                // Formulation codes and nutrient ratios as written: "80% WP", "NPK 15-15-15".
                preg_match_all('/\b(?:\d+(?:\.\d+)?%\s?)?[A-Z]{2,4}(?:\s?\d+(?:-\d+)+)?\b/u', $item, $m);
                foreach ($m[0] as $code) {
                    $terms[] = trim($code);
                }
            }
        }
        return array_values(array_unique(array_filter($terms)));
    }

    /** "1. a\n2. b" / "a; b" / "- a\n- b" -> ["a", "b"]. */
    public static function list(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($v) => self::sentence(is_string($v) ? $v : ''), $value)));
        }
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }
        $parts = preg_split('/\R+|;\s+|(?:^|\s)(?=\d+[.)]\s)/u', $value);
        $parts = array_map(fn ($p) => preg_replace('/^\s*(?:[-*•]|\d+[.)])\s*/u', '', $p), $parts);
        return array_values(array_filter(array_map(fn ($p) => self::sentence($p), $parts)));
    }

    private static function sentence(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        return preg_match('/[.!?]$/u', $s) ? $s : $s . '.';
    }

    private static function isNothing(string $s): bool
    {
        return in_array(mb_strtolower(trim($s, " .")), self::NOT_A_FINDING, true);
    }
}
