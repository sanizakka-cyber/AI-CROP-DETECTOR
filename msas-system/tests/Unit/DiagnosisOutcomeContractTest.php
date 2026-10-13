<?php

namespace Tests\Unit;

use App\Services\DiagnosisResultMapper;
use PHPUnit\Framework\TestCase;

/**
 * Phase 11 canonical contract: DiagnosisResultMapper's handling of the AI
 * engine's assessment_outcome. Pure array-in/array-out, no DB.
 */
class DiagnosisOutcomeContractTest extends TestCase
{
    private function aiResult(array $overrides = []): array
    {
        return array_merge([
            'contract_version'   => '2026-10-phase11',
            'assessment_outcome' => 'assessed',
            'image_quality'      => 'good',
            'image_issues'       => [],
            'photo_tips'         => [],
            'alternative_findings' => [],
            'what_cannot_be_confirmed' => null,
            'subject_name'       => 'Tomato',
            'scientific_name'    => 'Solanum lycopersicum',
            'disease'            => 'Late blight',
            'confidence'         => 78,
            'confidence_valid'   => true,
            'urgency'            => 'High',
            'health_status'      => 'Diseased',
            'ai_model'           => 'claude-sonnet-5',
        ], $overrides);
    }

    public function test_assessed_result_keeps_finding_and_stores_contract_fields(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult());

        $this->assertSame('assessed', $out['assessment_outcome']);
        $this->assertSame('Late blight', $out['disease_name']);
        $this->assertSame('sufficient', $out['confidence_decision']);
        $this->assertSame('good', $out['image_quality']);
        $this->assertSame('2026-10-phase11', $out['contract_version']);
        $this->assertSame('reviewed', $out['status']);
    }

    public function test_poor_image_gets_an_honest_headline_no_urgency_and_photo_tips(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult([
            'assessment_outcome' => 'poor_image', 'image_quality' => 'poor', 'image_issues' => ['blurred'],
            'photo_tips' => ['Hold the phone steady'], 'disease' => null, 'subject_name' => null,
            'scientific_name' => null, 'confidence' => null, 'urgency' => null, 'health_status' => null,
        ]));

        $this->assertSame('Photo too unclear to assess', $out['disease_name']);
        $this->assertNull($out['urgency_level'], 'No finding means no invented "Medium" urgency.');
        $this->assertNull($out['confidence_score']);
        $this->assertSame('poor_image', $out['confidence_decision']);
        $this->assertSame(['blurred'], $out['image_issues']);
        $this->assertSame(['Hold the phone steady'], $out['photo_tips']);
        $this->assertNull($out['subject_name']);
        $this->assertNull($out['scientific_name']);
    }

    public function test_unsupported_subject_is_labelled_as_such_not_as_ambiguous(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult([
            'assessment_outcome' => 'unsupported_subject', 'disease' => null, 'confidence' => null,
            'urgency' => null, 'health_status' => null,
        ]));

        $this->assertSame('unsupported_subject', $out['confidence_decision']);
        $this->assertSame('Photo does not show the scanned subject', $out['disease_name']);
    }

    public function test_uncertain_keeps_candidates_and_is_not_called_ambiguous(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult([
            'assessment_outcome' => 'uncertain', 'confidence' => 35, 'health_status' => 'Uncertain',
            'alternative_findings' => [['name' => 'Early blight', 'why_considered' => 'Similar lesions']],
            'what_cannot_be_confirmed' => 'Spores not visible',
        ]));

        $this->assertSame('uncertain', $out['confidence_decision']);
        $this->assertSame('Early blight', $out['alternative_findings'][0]['name']);
        $this->assertSame('Spores not visible', $out['what_cannot_be_confirmed']);
        $this->assertSame(35.0, $out['confidence_score'], 'The provider score is kept, not rewritten.');
    }

    public function test_an_unknown_outcome_value_is_ignored_not_trusted(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult(['assessment_outcome' => 'definitely_fine']));
        $this->assertNull($out['assessment_outcome']);
    }

    public function test_legacy_engine_reply_without_outcome_behaves_as_before(): void
    {
        $legacy = $this->aiResult();
        unset($legacy['assessment_outcome'], $legacy['contract_version'], $legacy['urgency']);
        $out = DiagnosisResultMapper::fromAiResult($legacy);

        $this->assertNull($out['assessment_outcome']);
        $this->assertSame('Medium', $out['urgency_level']);
        $this->assertSame('sufficient', $out['confidence_decision']);
    }

    public function test_engine_4xx_rejection_becomes_a_retake_photo_result(): void
    {
        $out = DiagnosisResultMapper::fromEngineRejection(422, ['detail' => ['accepted' => false, 'message' => 'This file could not be read as an image.']]);

        $this->assertSame('poor_image', $out['assessment_outcome']);
        $this->assertSame('reviewed', $out['status']);
        $this->assertSame('This file could not be read as an image.', $out['explanation']);
        $this->assertNull($out['confidence_score']);
        $this->assertNotEmpty($out['photo_tips']);
        $this->assertNull($out['vet_referral_advice'], 'Not "AI engine temporarily unavailable".');
    }

    public function test_engine_5xx_is_not_treated_as_a_photo_problem(): void
    {
        $this->assertNull(DiagnosisResultMapper::fromEngineRejection(503, ['detail' => 'down']));
        $this->assertNull(DiagnosisResultMapper::fromEngineRejection(502, null));
    }

    public function test_ai_unavailable_fallback_is_marked_as_provider_error(): void
    {
        $out = DiagnosisResultMapper::aiUnavailableFallback();
        $this->assertSame('provider_error', $out['assessment_outcome']);
        $this->assertSame('needs_review', $out['status']);
    }

    public function test_overlong_ai_text_is_capped_for_short_columns_so_the_insert_cannot_fail(): void
    {
        $long = str_repeat('Leaf ', 120); // 600 chars
        $out = DiagnosisResultMapper::fromAiResult($this->aiResult([
            'subject_name' => $long, 'health_status' => $long, 'detected_part' => $long,
            'recovery_period' => $long, // TEXT column: kept whole
        ]));

        $this->assertLessThanOrEqual(255, mb_strlen($out['subject_name']));
        $this->assertLessThanOrEqual(255, mb_strlen($out['health_status']));
        $this->assertLessThanOrEqual(255, mb_strlen($out['detected_part']));
        $this->assertSame($long, $out['recovery_period']);
    }
}
