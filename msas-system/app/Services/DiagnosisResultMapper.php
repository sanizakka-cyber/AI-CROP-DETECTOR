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
    /** Maps a successful AI response into the full Diagnosis field set. */
    public static function fromAiResult(array $aiResult): array
    {
        return [
            // Subject identification (auto-detected)
            'subject_name'              => $aiResult['subject_name']    ?? $aiResult['species']    ?? $aiResult['crop'] ?? $aiResult['animal'] ?? null,
            'scientific_name'           => $aiResult['scientific_name'] ?? $aiResult['latin_name']  ?? null,
            'detected_part'             => $aiResult['detected_part']   ?? $aiResult['body_part']   ?? null,
            'health_status'             => $aiResult['health_status']   ?? $aiResult['status']      ?? null,
            'severity_level'            => $aiResult['severity']        ?? $aiResult['severity_level'] ?? null,
            // Core
            'disease_name'              => $aiResult['disease'] ?? $aiResult['condition'] ?? $aiResult['diagnosis'] ?? $aiResult['disease_name'] ?? $aiResult['label'] ?? 'Requires expert review',
            'confidence_score'          => (float) ($aiResult['confidence'] ?? 0),
            'urgency_level'             => $aiResult['urgency'] ?? 'Medium',
            // Findings
            'symptoms_identified'       => $aiResult['symptoms_identified']   ?? null,
            'cause'                     => $aiResult['cause']                 ?? null,
            'environmental_factors'     => $aiResult['environmental_factors'] ?? null,
            'nutrient_deficiencies'     => $aiResult['nutrient_deficiencies'] ?? null,
            'pest_detection'            => $aiResult['pest_detection']        ?? null,
            // Treatment
            'first_aid_steps'           => $aiResult['first_aid'] ?? null,
            'recommended_medication'    => $aiResult['medication'] ?? $aiResult['fertilizer_recommendation'] ?? $aiResult['amendment_recommendation'] ?? null,
            'preventive_measures'       => $aiResult['preventive_measures']    ?? null,
            'fertilizer_recommendation' => $aiResult['fertilizer_recommendation'] ?? null,
            'recovery_period'           => $aiResult['recovery_period']       ?? null,
            'best_practices'            => $aiResult['best_practices']        ?? null,
            'vet_referral_advice'       => $aiResult['referral'] ?? $aiResult['vet_recommendation'] ?? null,
            // Explainability
            'explanation'               => $aiResult['explanation'] ?? null,
            'status'                    => 'reviewed',
        ];
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
        ];
    }
}
