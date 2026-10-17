<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 12.1:
 *  - below the configured confidence (default 65%) a result is never shown as
 *    one diagnosis: "Not certain", the possible conditions, and "confirm with a
 *    vet or extension officer" on the API, the web history page and the PDF,
 *    in every web language;
 *  - the AI disclaimer is on every result (web history and PDF);
 *  - the soil scan card never promises pH from a photo, in any web language.
 */
class LowConfidenceRuleTest extends TestCase
{
    use RefreshDatabase;

    private const LANGS = ['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff'];

    private function scan(User $u, array $over = []): Diagnosis
    {
        return Diagnosis::create(array_merge([
            'user_id' => $u->id, 'type' => 'livestock', 'status' => 'reviewed', 'language' => 'en',
            'subject_name' => 'Cattle', 'disease_name' => 'Ringworm', 'confidence_score' => 55,
            'urgency_level' => 'Medium', 'assessment_outcome' => 'assessed', 'image_quality' => 'good',
            'image_issues' => [], 'photo_tips' => [],
            'alternative_findings' => [
                ['name' => 'Mange', 'why_considered' => 'crusting'],
                ['name' => 'Lumpy skin disease', 'why_considered' => 'raised nodules'],
                ['name' => 'Dermatophilosis', 'why_considered' => 'scabs'],
            ],
            'recommended_medication' => 'Topical antifungal, as directed by a vet',
        ], $over));
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    public function test_api_marks_a_low_confidence_result_not_certain_with_its_possible_conditions(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $res = $this->api($u)->getJson('/api/diagnose/' . $this->scan($u)->id)->assertOk();

        $this->assertTrue($res->json('diagnosis.assessment.notCertain'));
        $this->assertEquals(65, $res->json('diagnosis.assessment.minConfidence'));
        $this->assertSame(['Ringworm', 'Mange', 'Lumpy skin disease'], $res->json('diagnosis.assessment.possibleConditions'));
    }

    public function test_at_or_above_the_threshold_one_diagnosis_is_shown(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        foreach ([65, 78] as $score) {
            $res = $this->api($u)->getJson('/api/diagnose/' . $this->scan($u, ['confidence_score' => $score])->id)->assertOk();
            $this->assertFalse($res->json('diagnosis.assessment.notCertain'), "{$score}%");
            $this->assertSame([], $res->json('diagnosis.assessment.possibleConditions'));
        }
    }

    public function test_the_threshold_is_configurable_and_applies_to_existing_scans(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, ['confidence_score' => 55]);
        config(['diagnosis.min_confidence' => 50]);
        $this->assertFalse($d->fresh()->is_not_certain);
        config(['diagnosis.min_confidence' => 80]);
        $this->assertTrue($this->scan($u, ['confidence_score' => 78])->is_not_certain);
    }

    public function test_rejected_photos_inconclusive_results_and_outages_keep_their_own_screens(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->assertFalse($this->scan($u, ['assessment_outcome' => 'poor_image', 'confidence_score' => 20])->is_not_certain);
        $this->assertFalse($this->scan($u, ['assessment_outcome' => 'uncertain', 'confidence_score' => 40])->is_not_certain);
        $this->assertFalse($this->scan($u, ['status' => 'needs_review', 'confidence_score' => null])->is_not_certain);
        // Pre-contract scans (no outcome stored) follow the rule too.
        $this->assertTrue($this->scan($u, ['assessment_outcome' => null, 'confidence_score' => 40])->is_not_certain);
    }

    public static function langs(): array
    {
        return array_map(fn ($l) => [$l], self::LANGS);
    }

    #[DataProvider('langs')]
    public function test_web_history_and_report_say_not_certain_in_every_language(string $lang): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u);
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        foreach (['Not certain', 'Please confirm with a vet or extension officer before treating.', 'AI guidance can be wrong; confirm with an expert.'] as $key) {
            $this->assertNotEmpty($strings[$key] ?? null, "{$lang}.json is missing \"{$key}\"");
        }

        $history = $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.history'))->assertOk()->getContent();
        $report = $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.report', $d))->assertOk()->getContent();

        foreach ([$history, $report] as $html) {
            $text = html_entity_decode($html, ENT_QUOTES);
            $this->assertStringContainsString('data-not-certain="1"', $html);
            $this->assertStringContainsString($strings['Not certain'], $text);
            $this->assertStringContainsString($strings['Please confirm with a vet or extension officer before treating.'], $text);
            $this->assertStringContainsString($strings['AI guidance can be wrong; confirm with an expert.'], $text);
            foreach (['Ringworm', 'Mange', 'Lumpy skin disease'] as $name) {
                $this->assertStringContainsString($name, $text);
            }
        }
        // The PDF never names one condition as "the" diagnosis.
        $this->assertStringContainsString('<div class="diag-name">' . e($strings['Not certain']) . '</div>', $report);
    }

    public function test_a_confident_result_shows_one_diagnosis_and_no_not_certain_block(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, ['confidence_score' => 82]);
        $history = $this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent();
        $report = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-not-certain', $history . $report);
        $this->assertStringContainsString('<div class="diag-name">Ringworm</div>', $report);
    }

    public static function outcomes(): array
    {
        return [
            'assessed'            => [['confidence_score' => 82]],
            'not certain'         => [['confidence_score' => 40]],
            'poor image'          => [['assessment_outcome' => 'poor_image', 'disease_name' => 'Photo too unclear to assess', 'confidence_score' => null, 'photo_tips' => ['Use daylight']]],
            'unsupported subject' => [['assessment_outcome' => 'unsupported_subject', 'disease_name' => 'Photo does not show the scanned subject', 'confidence_score' => null]],
            'inconclusive'        => [['assessment_outcome' => 'uncertain', 'confidence_score' => 30]],
            'AI unavailable'      => [['status' => 'needs_review', 'confidence_score' => null, 'disease_name' => 'Requires expert review']],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_the_ai_disclaimer_is_on_every_web_result_and_pdf(array $over): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, $over);
        foreach ([route('diagnostics.history'), route('diagnostics.report', $d)] as $url) {
            $html = $this->actingAs($u)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('data-ai-disclaimer="1"', $html, $url);
            $this->assertStringContainsString('AI guidance can be wrong; confirm with an expert.', $html, $url);
        }
    }

    #[DataProvider('langs')]
    public function test_the_soil_card_never_promises_ph_in_any_web_language(string $lang): void
    {
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        $this->assertArrayHasKey('Nutrients, Recommendations', $strings);
        $this->assertDoesNotMatchRegularExpression('/\bpH\b/u', $strings['Nutrients, Recommendations']);

        $u = User::factory()->create(['role' => 'farmer']);
        $html = $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.scan'))->assertOk()->getContent();
        $this->assertStringContainsString(e($strings['Nutrients, Recommendations']), $html);
        $this->assertDoesNotMatchRegularExpression('/\bpH\b/u', strip_tags($html), "pH promised on the {$lang} scan page");
    }
}
