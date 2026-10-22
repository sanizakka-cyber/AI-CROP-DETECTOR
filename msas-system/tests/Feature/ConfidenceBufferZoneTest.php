<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use App\Services\DiagnosisResultMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 12.2 buffer zone. One photo's confidence moved 65-78% between runs,
 * so a single 65% line flipped it between a diagnosis and "Not certain".
 * Now: below 65 "Not certain"; 65 up to 75 "Likely X, please confirm with a
 * vet or extension officer"; 75 and above the diagnosis. Both edges are
 * configurable. The engine's repeat-analysis summary is stored and exposed.
 */
class ConfidenceBufferZoneTest extends TestCase
{
    use RefreshDatabase;

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private function scan(User $u, float $conf, array $over = []): Diagnosis
    {
        return Diagnosis::create(array_merge([
            'user_id' => $u->id, 'type' => 'livestock', 'status' => 'reviewed', 'language' => 'en',
            'subject_name' => 'Cattle', 'disease_name' => 'Lumpy skin disease', 'confidence_score' => $conf,
            'assessment_outcome' => 'assessed', 'image_issues' => [], 'photo_tips' => [], 'alternative_findings' => [],
        ], $over));
    }

    public function test_bands_not_certain_likely_and_diagnosis(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        foreach ([[64.9, true, false], [65, false, true], [70, false, true], [74.9, false, true], [75, false, false], [90, false, false]] as [$c, $notCertain, $confirm]) {
            $d = $this->scan($u, $c);
            $this->assertSame($notCertain, $d->is_not_certain, "{$c}% not certain");
            $this->assertSame($confirm, $d->needs_confirmation, "{$c}% needs confirmation");
        }
    }

    public function test_the_band_never_applies_to_rejected_inconclusive_or_failed_scans(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->assertFalse($this->scan($u, 70, ['assessment_outcome' => 'uncertain'])->needs_confirmation);
        $this->assertFalse($this->scan($u, 70, ['assessment_outcome' => 'poor_image'])->needs_confirmation);
        $this->assertFalse($this->scan($u, 70, ['status' => 'needs_review'])->needs_confirmation);
    }

    public function test_edges_are_configurable(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, 72);
        config(['diagnosis.confirm_below' => 70]);
        $this->assertFalse($d->fresh()->needs_confirmation);
        // confirm_below can never sit under the Not-certain line.
        config(['diagnosis.confirm_below' => 50]);
        $this->assertSame(Diagnosis::minConfidence(), Diagnosis::confirmBelow());
    }

    public function test_api_web_and_pdf_say_likely_and_ask_to_confirm(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, 70);

        $a = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk()->json('diagnosis.assessment');
        $this->assertTrue($a['needsConfirmation']);
        $this->assertEquals(75, $a['confirmBelow']);
        $this->assertFalse($a['notCertain']);

        $history = $this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent();
        $report = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        foreach ([$history, $report] as $html) {
            $this->assertStringContainsString('data-needs-confirmation="1"', $html);
            $this->assertStringContainsString('Please confirm with a vet or extension officer before treating.', html_entity_decode($html, ENT_QUOTES));
        }
        $this->assertStringContainsString('<div class="diag-name" data-headline="1">Likely: Lumpy skin disease</div>', $report);
    }

    public function test_a_confident_result_has_no_likely_wording(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, 88);
        $report = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-needs-confirmation', $report);
        $this->assertStringContainsString('<div class="diag-name" data-headline="1">Lumpy skin disease</div>', $report);
    }

    public function test_the_engine_consensus_summary_is_stored_and_exposed(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $consensus = ['passes_requested' => 3, 'passes_completed' => 3, 'agreeing_passes' => 3,
                      'pass_confidences' => [70, 72, 71], 'confidence_spread' => 2];
        $row = DiagnosisResultMapper::fromAiResult([
            'disease' => 'Lumpy skin disease', 'confidence' => 71, 'confidence_valid' => true,
            'assessment_outcome' => 'assessed', 'consensus' => $consensus,
        ]);
        $this->assertSame($consensus, $row['ai_consensus']);
        $d = Diagnosis::create(array_merge($row, ['user_id' => $u->id, 'type' => 'livestock']));
        $this->assertSame($consensus, $this->api($u)->getJson("/api/diagnose/{$d->id}")->json('diagnosis.assessment.consensus'));
        // Older engines send no consensus block: nothing is invented.
        $this->assertNull(DiagnosisResultMapper::fromAiResult(['disease' => 'X', 'confidence' => 80])['ai_consensus']);
    }
}
