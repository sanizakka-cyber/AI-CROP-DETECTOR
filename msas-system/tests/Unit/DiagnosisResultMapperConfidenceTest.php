<?php

namespace Tests\Unit;

use App\Services\DiagnosisResultMapper;
use PHPUnit\Framework\TestCase;

/**
 * Confidence provenance and decision-policy coverage (spec Sections 4.1,
 * 4.3, 4.5). No DB — DiagnosisResultMapper is pure array-in/array-out.
 */
class DiagnosisResultMapperConfidenceTest extends TestCase
{
    private function baseAiResult(array $overrides = []): array
    {
        return array_merge([
            'subject_name'   => 'Maize',
            'disease'        => 'Leaf Blight',
            'confidence'     => 82,
            'confidence_valid' => true,
            'health_status'  => 'Diseased',
            'ai_model'       => 'claude-sonnet-5',
            'ai_model_version' => 'claude-sonnet-5',
            'raw_model_output' => 'subject_name | Maize\nconfidence | 82',
            'confidence_interpretation' => 'Self-reported certainty, not a calibrated probability.',
            'validation_status' => 'unvalidated',
        ], $overrides);
    }

    public function test_sufficient_confidence_above_threshold(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->baseAiResult(['confidence' => 90]));

        $this->assertSame(90.0, $out['confidence_score']);
        $this->assertSame('sufficient', $out['confidence_decision']);
        $this->assertSame('reviewed', $out['status']);
        $this->assertSame('primary_model', $out['generated_by']);
        $this->assertSame('unvalidated', $out['validation_status']);
        $this->assertSame(65.0, $out['decision_threshold']);
    }

    public function test_low_confidence_is_flagged_for_review_but_not_rejected(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->baseAiResult(['confidence' => 40]));

        $this->assertSame(40.0, $out['confidence_score']);
        $this->assertSame('low_confidence_review', $out['confidence_decision']);
        // Low confidence is still a genuine result — status stays 'reviewed',
        // not silently downgraded to needs_review (spec 4.3: a low-confidence
        // result is its own category, distinct from a malformed response).
        $this->assertSame('reviewed', $out['status']);
    }

    /**
     * A missing/unparseable confidence value must become a NULL score, never
     * a fabricated 0 — and must route to human review rather than being
     * presented as a normal "reviewed" result (spec 4.1 / 4.3 / 4.5).
     */
    public function test_malformed_confidence_becomes_null_score_and_needs_review(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->baseAiResult([
            'confidence' => null,
            'confidence_valid' => false,
        ]));

        $this->assertNull($out['confidence_score']);
        $this->assertSame('malformed_response', $out['confidence_decision']);
        $this->assertSame('needs_review', $out['status']);
    }

    /** Legacy/cached AI responses with no explicit confidence_valid key must still be handled — treated as valid only if a non-null confidence is present. */
    public function test_handles_ai_response_without_confidence_valid_key(): void
    {
        $out = DiagnosisResultMapper::fromAiResult([
            'subject_name' => 'Cassava',
            'disease'      => 'Mosaic Virus',
            'confidence'   => 77,
        ]);

        $this->assertSame(77.0, $out['confidence_score']);
        $this->assertSame('sufficient', $out['confidence_decision']);
        $this->assertNull($out['ai_model_name'], 'Must never invent provenance the AI engine did not actually send.');
        $this->assertSame('unvalidated', $out['validation_status'], 'Validation status must default honestly, not be invented as validated.');
    }

    public function test_ambiguous_health_status_is_flagged_regardless_of_score(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->baseAiResult(['confidence' => 95, 'health_status' => 'Uncertain']));

        $this->assertSame('ambiguous', $out['confidence_decision']);
    }

    public function test_ai_unavailable_fallback_never_fabricates_a_score(): void
    {
        $out = DiagnosisResultMapper::aiUnavailableFallback();

        $this->assertNull($out['confidence_score']);
        $this->assertSame('ai_unavailable', $out['confidence_decision']);
        $this->assertSame('needs_review', $out['status']);
        $this->assertNull($out['generated_by']);
        $this->assertNull($out['ai_model_name']);
    }

    public function test_provenance_fields_pass_through_from_ai_engine_response(): void
    {
        $out = DiagnosisResultMapper::fromAiResult($this->baseAiResult());

        $this->assertSame('claude-sonnet-5', $out['ai_model_name']);
        $this->assertSame('claude-sonnet-5', $out['ai_model_version']);
        $this->assertStringContainsString('Maize', $out['raw_model_output']);
        $this->assertStringContainsString('not a calibrated probability', $out['confidence_interpretation']);
    }
}
