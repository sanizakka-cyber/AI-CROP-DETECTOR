<?php

namespace App\Services;

/**
 * Single source of truth for turning an AI engine response into a
 * Diagnosis row's fields — extracted from DiagnosticController::analyze()
 * (the web scan flow), which captured the AI's full ~20-field response
 * while Api\DiagnoseApiController (the mobile scan flow) only extracted 6
 * of them and silently discarded the rest. Both controllers now build
 * their Diagnosis::create() payload through here, so a mobile scan and a
 * web scan of the same image produce the same richness of record instead
 * of mobile being a permanently impoverished second implementation.
 */
class DiagnosisResultMapper
{
    /**
     * Maps a successful AI response into the full Diagnosis field set.
     *
     * Originally written against /predict/crop and /predict/livestock's
     * field names only. /predict/soil and /predict/pest were already being
     * routed through this same mapper (DiagnosticController::analyze()
     * dispatches all four scan types here) but their response fields
     * (pest_name, chemical_control, immediate_action, ph_estimate,
     * nutrients, host_crops, ...) were never recognized by any of the
     * ??-chains below — meaning every pest scan silently fell through to
     * 'Requires expert review' with no treatment data saved at all, and
     * soil scans lost their pH/nutrient assessment. This was a live bug on
     * web, not something mobile introduced — fixed here so both clients
     * (which share this one mapper) get the real result.
     */
    /** Automated-acceptance line — mirrors Diagnosis::getConfidenceLabelAttribute()'s own band. Kept in one place so a future change to the threshold can't update one and miss the other. */
    public const CONFIDENCE_DECISION_THRESHOLD = 65.0;

    /** The configured threshold (config/diagnosis.php), defaulting to the constant above. */
    public static function threshold(): float
    {
        // Plain unit tests use the mapper without a booted app (no config).
        if (!app()->bound('config')) {
            return self::CONFIDENCE_DECISION_THRESHOLD;
        }
        return (float) config('diagnosis.min_confidence', self::CONFIDENCE_DECISION_THRESHOLD);
    }

    /** varchar(255) columns filled from AI text; capped so an over-long value can never fail the insert. */
    private const SHORT_COLUMNS = ['subject_name', 'scientific_name', 'detected_part', 'health_status', 'severity_level', 'urgency_level', 'image_quality'];

    private static function capShortColumns(array $row): array
    {
        foreach (self::SHORT_COLUMNS as $col) {
            if (isset($row[$col]) && is_string($row[$col]) && mb_strlen($row[$col]) > 255) {
                $row[$col] = rtrim(mb_substr($row[$col], 0, 252)) . '...';
            }
        }
        return $row;
    }

    public static function fromAiResult(array $aiResult): array
    {
        return self::capShortColumns(self::mapAiResult($aiResult));
    }

    private static function mapAiResult(array $aiResult): array
    {
        // Fields with no matching column get folded into the explanation
        // text as extra context rather than silently dropped or forced
        // into a semantically-wrong field.
        $extraContext = [];
        if (!empty($aiResult['host_crops']))        $extraContext[] = "Host crops: {$aiResult['host_crops']}";
        if (!empty($aiResult['spread_risk']))       $extraContext[] = "Spread risk: {$aiResult['spread_risk']}";
        if (!empty($aiResult['economic_threshold'])) $extraContext[] = "Treatment threshold: {$aiResult['economic_threshold']}";
        if (!empty($aiResult['suitable_crops']))    $extraContext[] = "Suitable crops for this soil: {$aiResult['suitable_crops']}";
        $explanation = $aiResult['explanation'] ?? null;
        if ($extraContext) {
            $explanation = trim(($explanation ? $explanation . ' ' : '') . implode('. ', $extraContext) . '.');
        }

        // Soil's 'nutrients' (apparent profile) and 'nutrient_deficiencies'
        // (specific deficiencies) are two distinct pieces of information —
        // keep both when present instead of one silently shadowing the other.
        if (!empty($aiResult['nutrients']) && !empty($aiResult['nutrient_deficiencies'])) {
            $nutrientInfo = "Nutrient profile: {$aiResult['nutrients']}. Deficiencies: {$aiResult['nutrient_deficiencies']}";
        } else {
            $nutrientInfo = $aiResult['nutrient_deficiencies'] ?? $aiResult['nutrients'] ?? null;
        }

        // /predict/crop and /predict/livestock return a dedicated
        // 'organic_treatment' field (pest scans already cover this via
        // 'biological_control' below); there's no separate column for it,
        // so fold it into best_practices rather than silently dropping it —
        // same "no matching column" pattern as $extraContext above.
        $bestPractices = $aiResult['best_practices'] ?? $aiResult['biological_control'] ?? null;
        if (!empty($aiResult['organic_treatment']) && !str_starts_with(strtolower($aiResult['organic_treatment']), 'no')) {
            $bestPractices = trim("Organic/natural option: {$aiResult['organic_treatment']} " . ($bestPractices ?? ''));
        }

        // ── Confidence provenance (spec 4.5) ──────────────────────────────
        // $aiResult['confidence'] is null (not 0) whenever the AI engine
        // couldn't parse a confidence value at all, and 'confidence_valid'
        // is false for both that case AND an out-of-range value the engine
        // clamped for display. Neither case is a genuine measured 0% —
        // storing null here (never a fabricated number) is what lets
        // Diagnosis::getConfidenceLabelAttribute() show "N/A" honestly
        // instead of a confident-looking "0% — Low Confidence".
        $rawConfidence   = $aiResult['confidence'] ?? null;
        $confidenceValid = array_key_exists('confidence_valid', $aiResult) ? (bool) $aiResult['confidence_valid'] : $rawConfidence !== null;
        $confidenceScore = $confidenceValid && $rawConfidence !== null ? (float) $rawConfidence : null;

        $healthStatus = $aiResult['health_status'] ?? $aiResult['status'] ?? null;
        $isAmbiguous  = $healthStatus !== null && in_array(strtolower($healthStatus), ['uncertain', 'multi-issue'], true);

        // Phase 11 contract: the engine states which recoverable state this
        // result is in. Absent on responses from an engine predating it.
        $outcome = $aiResult['assessment_outcome'] ?? null;
        if (!in_array($outcome, self::OUTCOMES, true)) {
            $outcome = null;
        }

        $confidenceDecision = match (true) {
            !$confidenceValid                                               => 'malformed_response',
            $outcome === 'unsupported_subject'                              => 'unsupported_subject',
            $outcome === 'poor_image'                                       => 'poor_image',
            $outcome === 'uncertain'                                        => 'uncertain',
            $isAmbiguous                                                    => 'ambiguous',
            $confidenceScore !== null && $confidenceScore < self::threshold() => 'low_confidence_review',
            default                                                         => 'sufficient',
        };

        return [
            // Subject identification (auto-detected)
            'subject_name'              => $aiResult['subject_name']    ?? $aiResult['pest_name'] ?? $aiResult['species'] ?? $aiResult['crop'] ?? $aiResult['animal'] ?? null,
            'scientific_name'           => $aiResult['scientific_name'] ?? $aiResult['latin_name']  ?? null,
            'detected_part'             => $aiResult['detected_part']   ?? $aiResult['body_part']   ?? null,
            'health_status'             => $healthStatus,
            'severity_level'            => $aiResult['severity']        ?? $aiResult['severity_level'] ?? null,
            // Core
            'disease_name'              => $aiResult['disease'] ?? $aiResult['pest_name'] ?? $aiResult['condition'] ?? $aiResult['diagnosis'] ?? $aiResult['disease_name'] ?? $aiResult['label'] ?? self::outcomeHeadline($outcome),
            'confidence_score'          => $confidenceScore,
            // Never invent an urgency for a result with no finding.
            'urgency_level'             => $aiResult['urgency'] ?? ($outcome === null ? 'Medium' : null),
            // Findings
            'symptoms_identified'       => $aiResult['symptoms_identified']   ?? $aiResult['damage_type'] ?? null,
            'cause'                     => $aiResult['cause']                 ?? $aiResult['pest_type']   ?? null,
            'environmental_factors'     => $aiResult['environmental_factors'] ?? (!empty($aiResult['ph_estimate']) ? "Estimated soil pH: {$aiResult['ph_estimate']}" : null),
            'nutrient_deficiencies'     => $nutrientInfo,
            'pest_detection'            => $aiResult['pest_detection']        ?? null,
            // Treatment
            'first_aid_steps'           => $aiResult['first_aid'] ?? $aiResult['immediate_action'] ?? null,
            'recommended_medication'    => $aiResult['medication'] ?? $aiResult['chemical_control'] ?? $aiResult['fertilizer_recommendation'] ?? $aiResult['amendment_recommendation'] ?? null,
            'preventive_measures'       => $aiResult['preventive_measures']    ?? $aiResult['cultural_control'] ?? null,
            'fertilizer_recommendation' => $aiResult['fertilizer_recommendation'] ?? null,
            'recovery_period'           => $aiResult['recovery_period']       ?? null,
            'best_practices'            => $bestPractices,
            'vet_referral_advice'       => $aiResult['referral'] ?? $aiResult['vet_recommendation'] ?? null,
            // Explainability
            'explanation'               => $explanation,
            // A malformed/unparseable confidence value means the model's
            // response couldn't be trusted as-is — route to the same human
            // review queue as an AI-unavailable scan, rather than marking
            // it "reviewed" with a silently-blanked confidence.
            'status'                    => $confidenceDecision === 'malformed_response' ? 'needs_review' : 'reviewed',

            // AI confidence provenance (spec 4.5) — never invented when the
            // AI engine response (e.g. from a response cached before this
            // field existed) doesn't carry it.
            'ai_model_name'             => $aiResult['ai_model'] ?? null,
            'ai_model_version'          => $aiResult['ai_model_version'] ?? null,
            'raw_model_output'          => $aiResult['raw_model_output'] ?? null,
            'confidence_interpretation' => $aiResult['confidence_interpretation'] ?? null,
            'confidence_decision'       => $confidenceDecision,
            'decision_threshold'        => self::threshold(),
            'validation_status'         => $aiResult['validation_status'] ?? 'unvalidated',
            'generated_by'              => 'primary_model',

            // Phase 11 canonical contract
            'assessment_outcome'        => $outcome,
            'image_quality'             => $aiResult['image_quality'] ?? null,
            'image_issues'              => array_values((array) ($aiResult['image_issues'] ?? [])) ?: null,
            'photo_tips'                => array_values((array) ($aiResult['photo_tips'] ?? [])) ?: null,
            'alternative_findings'      => array_values((array) ($aiResult['alternative_findings'] ?? [])) ?: null,
            'what_cannot_be_confirmed'  => $aiResult['what_cannot_be_confirmed'] ?? null,
            'contract_version'          => $aiResult['contract_version'] ?? null,
            // Kept in best_practices too (above) so older app builds still show it.
            'organic_options'           => self::organicOptions($aiResult),
        ];
    }

    private static function organicOptions(array $aiResult): ?string
    {
        $opt = $aiResult['organic_treatment'] ?? $aiResult['biological_control'] ?? null;
        if (!is_string($opt) || trim($opt) === '' || str_starts_with(strtolower($opt), 'no')) {
            return null;
        }
        return $opt;
    }

    public const OUTCOMES = ['assessed', 'uncertain', 'poor_image', 'unsupported_subject'];

    /** Shown as the record's headline when there is no finding to name. */
    public static function outcomeHeadline(?string $outcome): string
    {
        return match ($outcome) {
            'poor_image'          => 'Photo too unclear to assess',
            'unsupported_subject' => 'Photo does not show the scanned subject',
            'uncertain'           => 'Inconclusive: more evidence needed',
            default               => 'Requires expert review',
        };
    }

    /**
     * The engine answered with a 4xx: it read the request and rejected the
     * image itself (undecodable or unusable file). That is a photo problem
     * the farmer can fix, not an AI outage, so it must not be stored as
     * "AI engine temporarily unavailable, an expert will review".
     */
    public static function fromEngineRejection(int $httpStatus, ?array $body): ?array
    {
        if ($httpStatus < 400 || $httpStatus >= 500) {
            return null;
        }
        $detail  = $body['detail'] ?? null;
        $message = is_array($detail) ? ($detail['message'] ?? null) : (is_string($detail) ? $detail : null);

        return array_merge(self::aiUnavailableFallback(), [
            'disease_name'             => self::outcomeHeadline('poor_image'),
            'status'                   => 'reviewed',
            'urgency_level'            => null,
            'vet_referral_advice'      => null,
            'explanation'              => $message ?: 'The photo could not be read. Please retake it.',
            'confidence_decision'      => 'poor_image',
            'assessment_outcome'       => 'poor_image',
            'image_quality'            => 'poor',
            'photo_tips'               => ['Use a clear JPEG or PNG photo taken with the camera.', 'Hold the phone steady and fill the frame with the subject.'],
        ]);
    }

    /** The AI genuinely produced no result (unavailable/timeout/bad response) — honest fallback, never a fabricated diagnosis. */
    public static function aiUnavailableFallback(): array
    {
        return [
            'subject_name'              => null,
            'scientific_name'           => null,
            'detected_part'             => null,
            'health_status'             => null,
            'severity_level'            => null,
            'disease_name'              => 'Pending Expert Review',
            // AI never ran — this is "no score", not a genuine 0%. Never
            // fabricate a number here; downstream views must render "AI
            // Analysis Unavailable" whenever confidence_score is null.
            'confidence_score'          => null,
            'urgency_level'             => 'Medium',
            'symptoms_identified'       => null,
            'cause'                     => '',
            'first_aid_steps'           => '',
            'recommended_medication'    => '',
            'environmental_factors'     => null,
            'nutrient_deficiencies'     => null,
            'pest_detection'            => null,
            'preventive_measures'       => null,
            'fertilizer_recommendation' => null,
            'recovery_period'           => null,
            'best_practices'            => null,
            'vet_referral_advice'       => 'Our AI engine is temporarily unavailable. An expert will review your scan and respond shortly.',
            'explanation'               => null,
            'status'                    => 'needs_review',
            // Confidence provenance — the model never ran, so every field
            // here is explicitly absent, not a guessed/default value.
            'ai_model_name'             => null,
            'ai_model_version'          => null,
            'raw_model_output'          => null,
            'confidence_interpretation' => null,
            'confidence_decision'       => 'ai_unavailable',
            'decision_threshold'        => self::threshold(),
            'validation_status'         => 'unvalidated',
            'generated_by'              => null,
            'assessment_outcome'        => 'provider_error',
            'image_quality'             => null,
            'image_issues'              => null,
            'photo_tips'                => null,
            'alternative_findings'      => null,
            'what_cannot_be_confirmed'  => null,
            'contract_version'          => null,
        ];
    }
}
