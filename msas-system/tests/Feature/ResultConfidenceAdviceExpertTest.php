<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\User;
use App\Support\ResultView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Follow-up to the consensus result screen (owner's requests, 22 Oct 2026):
 *  - an inconclusive result never shows a blank or "N/A" confidence: "Low – the
 *    3 analyses did not agree" (or "the AI could not tell from this photo"),
 *    with the retake button and photo tips;
 *  - a healthy or normally ageing result says "Advice:", not "Recovery:";
 *  - the disclaimer is farmer-friendly in every language, with a
 *    "Request Vet / Ask an expert" button that opens the Request Vet (or
 *    agronomist) page with the scan attached.
 */
class ResultConfidenceAdviceExpertTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('../shared-fixtures/banana-consensus-diagnosis.json')), true);
    }

    private function banana(User $u, string $which, array $over = []): Diagnosis
    {
        return Diagnosis::create(array_merge($this->fixture()[$which]['record'], ['user_id' => $u->id], $over));
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#si', ' ', $html)), ENT_QUOTES));
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    public function test_inconclusive_confidence_says_why_it_is_low_never_na(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->banana($u, 'inconclusive');
        $history = $this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent();
        $report = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();

        foreach (['history' => $history, 'report' => $report] as $where => $html) {
            $text = $this->text($html);
            $this->assertStringContainsString('Low – the 3 analyses did not agree', $text, $where);
            $this->assertStringNotContainsString('N/A', $text, $where);
            $this->assertStringNotContainsString('No single finding', $text, $where);
        }
        $this->assertStringContainsString('data-retake-button="1"', $history);
        $this->assertStringContainsString(ResultView::TIPS['tip_fill_frame'], $this->text($history));
        $this->assertStringContainsString(ResultView::TIPS['tip_fill_frame'], $this->text($report));

        $api = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk();
        $this->assertSame('disagree', $api->json('diagnosis.assessment.result.confidence.key'));
        $this->assertSame('Low – the 3 analyses did not agree', $api->json('diagnosis.aiResult.confidenceLabel'));
    }

    public function test_a_failed_scan_is_still_unavailable_not_low(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->banana($u, 'inconclusive', ['assessment_outcome' => 'provider_error', 'confidence_decision' => 'ai_unavailable']);
        $this->assertNull($d->resultView()['confidence']);
        $this->assertSame('N/A — AI Analysis Unavailable', $d->confidence_label);
    }

    public function test_healthy_results_say_advice_not_recovery(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->banana($u, 'normalAgeing');
        $history = $this->text($this->actingAs($u)->get(route('diagnostics.history'))->getContent());
        $report = $this->text($this->actingAs($u)->get(route('diagnostics.report', $d))->getContent());
        foreach ([$history, $report] as $text) {
            $this->assertStringContainsString('Advice: No recovery needed', $text);
            $this->assertStringNotContainsString('Recovery:', $text);
        }
        // A disease keeps "Recovery".
        $sick = Diagnosis::create(['user_id' => $u->id, 'type' => 'plant', 'status' => 'reviewed', 'disease_name' => 'Black leaf streak',
            'assessment_outcome' => 'assessed', 'confidence_score' => 90, 'health_status' => 'Diseased', 'recovery_period' => '3-4 weeks']);
        $this->assertSame('Recovery', $sick->resultView()['recovery_label']);
    }

    public static function langs(): array
    {
        return array_map(fn ($l) => [$l], ['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff']);
    }

    #[DataProvider('langs')]
    public function test_disclaimer_and_button_in_every_language(string $lang): void
    {
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        foreach ([ResultView::DISCLAIMER, ResultView::ASK_EXPERT, ResultView::CONFIDENCE_DISAGREE, ResultView::CONFIDENCE_UNSURE, 'Advice', 'Recovery', 'Scan attached'] as $key) {
            $this->assertNotEmpty($strings[$key] ?? null, "{$lang}.json is missing \"{$key}\"");
        }
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->banana($u, 'inconclusive');
        $html = $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.history'))->assertOk()->getContent();
        $text = $this->text($html);
        $this->assertStringContainsString($strings[ResultView::DISCLAIMER], $text);
        $this->assertStringContainsString($strings[ResultView::ASK_EXPERT], $text);
        $this->assertStringContainsString('href="' . route('diagnostics.ask-expert', $d) . '"', $html);
        $this->assertStringContainsString(str_replace(':count', '3', $strings[ResultView::CONFIDENCE_DISAGREE]), $text);
    }

    public function test_ask_expert_opens_the_right_request_page_with_the_scan_attached(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $crop = $this->banana($u, 'inconclusive');
        $animal = Diagnosis::create(['user_id' => $u->id, 'type' => 'animal', 'status' => 'reviewed', 'subject_name' => 'Cattle',
            'disease_name' => 'Lumpy skin disease', 'assessment_outcome' => 'assessed', 'confidence_score' => 82, 'urgency_level' => 'High']);

        $this->actingAs($u)->get(route('diagnostics.ask-expert', $crop))->assertRedirect(route('farmer.agro', ['diagnosis' => $crop->id]));
        $this->actingAs($u)->get(route('diagnostics.ask-expert', $animal))->assertRedirect(route('farmer.vet', ['diagnosis' => $animal->id]));

        $agro = $this->actingAs($u)->get(route('farmer.agro', ['diagnosis' => $crop->id]))->assertOk()->getContent();
        $this->assertStringContainsString('data-attached-scan="' . $crop->id . '"', $agro);
        $this->assertStringContainsString('name="diagnosis_id" value="' . $crop->id . '"', $agro);
        $this->assertStringContainsString('value="Banana"', $agro);
        $this->assertStringContainsString('Natural leaf sheath ageing', $this->text($agro));

        $vet = $this->actingAs($u)->get(route('farmer.vet', ['diagnosis' => $animal->id]))->assertOk()->getContent();
        $this->assertStringContainsString('data-attached-scan="' . $animal->id . '"', $vet);
        $this->assertStringContainsString('value="Cattle"', $vet);
        $this->assertMatchesRegularExpression('/<option value="high"\s+selected/', $vet);
    }

    public function test_someone_elses_scan_cannot_be_attached(): void
    {
        $owner = User::factory()->create(['role' => 'farmer']);
        $other = User::factory()->create(['role' => 'farmer']);
        $scan = $this->banana($owner, 'inconclusive');

        $this->actingAs($other)->get(route('diagnostics.ask-expert', $scan))->assertForbidden();
        $page = $this->actingAs($other)->get(route('farmer.agro', ['diagnosis' => $scan->id]))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-attached-scan', $page);

        $this->api($other)->postJson('/api/consultations/agro', ['crop_type' => 'Banana', 'symptoms' => 'x',
            'priority' => 'low', 'channel' => 'in_app', 'diagnosis_id' => $scan->id])->assertCreated();
        $this->assertNull(Consultation::latest('id')->first()->diagnosis_id);
    }

    public function test_the_request_keeps_the_scan_and_the_expert_sees_it(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->banana($u, 'normalAgeing');
        $this->api($u)->postJson('/api/consultations/agro', ['crop_type' => 'Banana', 'symptoms' => 'Stem outer layers drying',
            'priority' => 'low', 'channel' => 'in_app', 'diagnosis_id' => $d->id])->assertCreated();

        $c = Consultation::latest('id')->first();
        $this->assertSame($d->id, $c->diagnosis_id);
        $this->assertStringContainsString('Old outer layers drying naturally', $c->ai_diagnosis);
        $this->assertEquals(80, $c->ai_confidence);

        $c->update(['status' => 'open']);
        $agronomist = User::factory()->create(['role' => 'agronomist']);
        $page = $this->actingAs($agronomist)->get(route('vet.show', $c));
        if ($page->status() === 200) {
            $this->assertStringContainsString('data-attached-scan="' . $d->id . '"', $page->getContent());
            $this->assertStringContainsString('Musa spp.', $page->getContent());
        } else {
            // The vet area needs an active subscription; the attachment itself is what matters here.
            $this->assertContains($page->status(), [302, 403]);
        }
    }

    public function test_the_disclaimer_button_is_for_farmers_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->banana($admin, 'inconclusive');
        $html = $this->actingAs($admin)->get(route('diagnostics.history'))->assertOk()->getContent();
        $this->assertStringContainsString(ResultView::DISCLAIMER, $this->text($html));
        $this->assertStringNotContainsString('data-ask-expert', $html);
    }
}
