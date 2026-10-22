<?php

namespace Tests\Unit;

use App\Models\Diagnosis;
use Tests\TestCase;

/**
 * A scan the AI judged without a single score (unclear photo, healthy
 * animal, several possibilities) must not be labelled "AI Analysis
 * Unavailable": that wording is only for scans the AI never completed.
 */
class ConfidenceLabelTest extends TestCase
{
    public function test_scored_scan_shows_percent_and_band(): void
    {
        $d = new Diagnosis(['confidence_score' => 85, 'assessment_outcome' => 'assessed']);
        $this->assertSame('85% — High Confidence', $d->confidence_label);
        $this->assertFalse($d->has_no_single_finding);
    }

    public function test_rejected_photo_has_no_score_to_show(): void
    {
        foreach (['poor_image', 'unsupported_subject'] as $outcome) {
            $d = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => $outcome]);
            $this->assertSame('N/A — No single finding', $d->confidence_label, $outcome);
            $this->assertTrue($d->has_no_single_finding, $outcome);
        }
    }

    /** An inconclusive result says why its confidence is low, never "N/A". */
    public function test_inconclusive_without_score_says_why_confidence_is_low(): void
    {
        $unsure = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => 'uncertain']);
        $this->assertSame('Low – the AI could not tell from this photo', $unsure->confidence_label);
        $this->assertTrue($unsure->has_no_single_finding);

        $split = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => 'uncertain',
            'ai_consensus' => ['passes_completed' => 3, 'pass_outcomes' => ['assessed', 'assessed', 'uncertain']]]);
        $this->assertSame('Low – the 3 analyses did not agree', $split->confidence_label);

        $allUnsure = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => 'uncertain',
            'ai_consensus' => ['passes_completed' => 3, 'pass_outcomes' => ['uncertain', 'uncertain', 'uncertain']]]);
        $this->assertSame('Low – the AI could not tell from this photo', $allUnsure->confidence_label);
    }

    public function test_failed_or_legacy_scan_is_unavailable(): void
    {
        $failed = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => 'provider_error', 'confidence_decision' => 'ai_unavailable']);
        $legacy = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => null]);
        foreach ([$failed, $legacy] as $d) {
            $this->assertSame('N/A — AI Analysis Unavailable', $d->confidence_label);
            $this->assertFalse($d->has_no_single_finding);
        }
    }

    public function test_label_is_translated(): void
    {
        app()->setLocale('fr');
        $d = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => 'uncertain']);
        $this->assertSame("Faible – l'IA n'a pas pu conclure avec cette photo", $d->confidence_label);
    }
}
