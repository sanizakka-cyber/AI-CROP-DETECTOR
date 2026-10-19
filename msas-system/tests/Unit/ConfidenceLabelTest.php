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

    public function test_judged_scan_without_score_is_no_single_finding(): void
    {
        foreach (['uncertain', 'poor_image', 'unsupported_subject'] as $outcome) {
            $d = new Diagnosis(['confidence_score' => null, 'assessment_outcome' => $outcome]);
            $this->assertSame('N/A — No single finding', $d->confidence_label, $outcome);
            $this->assertTrue($d->has_no_single_finding, $outcome);
        }
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
        $this->assertSame('N/A — Pas de résultat unique', $d->confidence_label);
    }
}
