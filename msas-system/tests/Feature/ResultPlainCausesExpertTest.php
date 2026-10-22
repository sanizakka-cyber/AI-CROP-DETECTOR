<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use App\Support\ResultView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Follow-up to #297 (owner, 23 Oct 2026), from a live mango scan
 * (MSAS-SCN-20261022-000084, Low 58%) whose explanation said "algal leaf
 * spot" and "anthracnose" while "Possible causes" listed "Orange rust spots /
 * Leaf burn / Leaf injury":
 *  - the farmer's explanation of an inconclusive result is built from the
 *    same listed causes, in the same plain names; the engine's own words
 *    (technical and Latin names) only in staff details and the PDF appendix;
 *  - crops, soil and pests say "agronomist or extension officer", livestock
 *    says "vet", in every language;
 *  - the disclaimer and ask-an-expert button are on the result, and for a
 *    crop the button opens the agronomist request;
 *  - the page header keeps the title alone in the top bar (New Scan in the
 *    page toolbar), and the confidence sits on a solid dark band.
 */
class ResultPlainCausesExpertTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE_EXPLANATION = 'Orange velvety spots typical of algal leaf spot (Cephaleuros virescens); the dark sunken patches could also be anthracnose (Colletotrichum gloeosporioides).';

    private function mango(User $u, array $over = []): Diagnosis
    {
        return Diagnosis::create(array_merge([
            'user_id' => $u->id, 'type' => 'plant', 'status' => 'reviewed', 'language' => 'en',
            'subject_name' => 'Mango', 'scientific_name' => 'Mangifera indica', 'detected_part' => 'leaf',
            'disease_name' => 'Algal leaf spot', 'farmer_name' => 'Orange rust spots',
            'condition_label' => 'algal_spot', 'health_status' => 'Unhealthy',
            'confidence_score' => 58, 'assessment_outcome' => 'assessed',
            'explanation' => self::ENGINE_EXPLANATION,
            'alternative_findings' => [
                ['name' => 'Sunscald', 'farmer_name' => 'Leaf burn', 'condition_label' => 'sun_damage', 'why_considered' => 'Pale dry patches'],
                ['name' => 'Mechanical injury', 'farmer_name' => 'Leaf injury', 'condition_label' => 'injury', 'why_considered' => 'Torn edges'],
                ['name' => 'Anthracnose (Colletotrichum gloeosporioides)', 'farmer_name' => 'Dark sunken spots', 'condition_label' => 'anthracnose', 'why_considered' => 'Dark patches'],
            ],
        ], $over));
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#si', ' ', $html)), ENT_QUOTES));
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private const BUILT = 'The signs in this photo could be one of these: Orange rust spots, Leaf burn, Leaf injury. The AI cannot tell which one from this photo.';

    public function test_the_explanation_names_the_same_three_causes_as_the_list(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u);
        $view = $d->resultView($u);

        $this->assertTrue($view['inconclusive']);
        $this->assertSame(['Orange rust spots', 'Leaf burn', 'Leaf injury'], $view['possible_causes']);
        $this->assertSame(['key' => 'explain_causes', 'text' => self::BUILT, 'causes' => $view['possible_causes']], $view['explanation']);

        $history = $this->text($this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent());
        $this->assertStringContainsString(self::BUILT, $history);
        // One list of causes: no second "Other possibilities considered" naming a fourth.
        $this->assertStringNotContainsString('Other possibilities considered', $history);
        $this->assertStringNotContainsString('Dark sunken spots', $history);
        foreach (['algal', 'anthracnose', 'Cephaleuros', 'Colletotrichum', 'Mangifera'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $history, "farmer's page shows \"{$word}\"");
        }

        $api = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk();
        $this->assertSame(self::BUILT, $api->json('diagnosis.aiResult.explanation'));
        $this->assertSame('explain_causes', $api->json('diagnosis.assessment.result.explanation.key'));
        $this->assertSame($api->json('diagnosis.assessment.possibleConditions'), $api->json('diagnosis.assessment.result.explanation.causes'));
        $this->assertNull($api->json('diagnosis.technical'));
    }

    public function test_the_engine_explanation_stays_in_staff_details_and_the_pdf_appendix(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u);
        $pdf = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        [$farmerPart, $appendix] = explode('data-technical-appendix="1"', $pdf, 2);
        $this->assertStringContainsString(self::BUILT, $this->text($farmerPart));
        $this->assertStringNotContainsStringIgnoringCase('anthracnose', $this->text($farmerPart));
        $this->assertStringContainsString('Colletotrichum gloeosporioides', $this->text($appendix));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->mango($admin);
        $staff = $this->actingAs($admin)->get(route('diagnostics.history'))->assertOk()->getContent();
        $this->assertStringContainsString('data-technical-details="1"', $staff);
        $this->assertStringContainsString('Cephaleuros virescens', $this->text($staff));
    }

    public function test_a_confident_result_keeps_the_engine_explanation_in_plain_words(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u, ['confidence_score' => 88, 'alternative_findings' => [],
            'explanation' => 'Orange velvety spots with necrosis at the edges.']);
        $this->assertSame(['key' => null, 'text' => 'Orange velvety spots with dead patches at the edges.', 'causes' => []], $d->resultView()['explanation']);
    }

    public function test_no_causes_says_the_ai_cannot_tell(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u, ['confidence_score' => null, 'assessment_outcome' => 'uncertain', 'disease_name' => 'Unknown',
            'farmer_name' => null, 'alternative_findings' => []]);
        $this->assertSame([], $d->resultView()['possible_causes']);
        $this->assertSame('explain_unsure', $d->resultView()['explanation']['key']);
        $this->assertSame(ResultView::EXPLAIN_UNSURE, $d->resultView()['explanation']['text']);
    }

    public static function types(): array
    {
        return [['plant', 'agronomist'], ['soil', 'agronomist'], ['pest', 'agronomist'], ['animal', 'vet'], ['livestock', 'vet']];
    }

    #[DataProvider('types')]
    public function test_who_to_ask_depends_on_the_scan_type(string $type, string $expert): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u, ['type' => $type]);
        $html = $this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent();
        $text = $this->text($html);
        $vet = $expert === 'vet';

        $this->assertSame($expert, $d->resultView()['expert']['key']);
        $this->assertStringContainsString($vet ? ResultView::DISCLAIMER : ResultView::DISCLAIMER_CROP, $text);
        $this->assertStringContainsString($vet ? ResultView::ASK_EXPERT : ResultView::ASK_EXPERT_CROP, $text);
        $this->assertStringContainsString($vet ? ResultView::CONFIRM : ResultView::CONFIRM_CROP, $text);
        $this->assertStringContainsString('data-ask-expert="' . $expert . '"', $html);
        if (!$vet) {
            foreach ([ResultView::DISCLAIMER, ResultView::ASK_EXPERT, ResultView::CONFIRM] as $vetLine) {
                $this->assertStringNotContainsString($vetLine, $text);
            }
        }
    }

    public function test_a_likely_crop_result_and_its_steps_ask_an_agronomist(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u, ['confidence_score' => 70, 'alternative_findings' => []]);
        $this->assertSame('likely', $d->resultView()['kind']);
        $text = $this->text($this->actingAs($u)->get(route('diagnostics.history'))->getContent());
        $this->assertStringContainsString(ResultView::LIKELY_CONFIRM_CROP, $text);
        $this->assertStringContainsString(ResultView::STEPS['confirm_before_treating'], $text);
        $this->assertStringNotContainsString(ResultView::LIKELY_CONFIRM, $text);
        $this->assertStringNotContainsString('extension officer or vet to confirm', $text);
    }

    public function test_the_crop_button_opens_the_agronomist_request_with_the_scan(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->mango($u);
        $html = $this->actingAs($u)->get(route('diagnostics.history'))->getContent();
        $this->assertStringContainsString('data-ai-disclaimer="1"', $html);
        $this->assertStringContainsString('href="' . route('diagnostics.ask-expert', $d) . '"', $html);
        $this->actingAs($u)->get(route('diagnostics.ask-expert', $d))->assertRedirect(route('farmer.agro', ['diagnosis' => $d->id]));
    }

    public static function langs(): array
    {
        return array_map(fn ($l) => [$l], ['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff']);
    }

    #[DataProvider('langs')]
    public function test_the_new_lines_are_in_every_web_language(string $lang): void
    {
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        foreach ([ResultView::DISCLAIMER_CROP, ResultView::ASK_EXPERT_CROP, ResultView::CONFIRM_CROP, ResultView::LIKELY_CONFIRM_CROP,
                  ResultView::EXPLAIN_CAUSES, ResultView::EXPLAIN_UNSURE, ResultView::STEPS['confirm_before_treating']] as $key) {
            $this->assertNotEmpty($strings[$key] ?? null, "{$lang}.json is missing \"{$key}\"");
        }
        $this->assertStringContainsString(':causes', $strings[ResultView::EXPLAIN_CAUSES]);

        $u = User::factory()->create(['role' => 'farmer']);
        $this->mango($u);
        $text = $this->text($this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.history'))->getContent());
        $this->assertStringContainsString($strings[ResultView::DISCLAIMER_CROP], $text);
        $this->assertStringContainsString($strings[ResultView::ASK_EXPERT_CROP], $text);
        $this->assertStringContainsString(str_replace(':causes', 'Orange rust spots, Leaf burn, Leaf injury', $strings[ResultView::EXPLAIN_CAUSES]), $text);
    }

    public function test_header_title_alone_in_the_top_bar_and_a_solid_confidence_band(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->mango($u);
        $html = $this->actingAs($u)->get(route('diagnostics.history'))->getContent();

        [, $bar] = explode('data-page-header="1"', $html, 2);
        [$bar] = explode('<!-- Right: Search', $bar, 2);
        $this->assertStringContainsString('data-page-title="1"', $bar);
        $this->assertStringNotContainsString('New Scan', $bar);
        $this->assertMatchesRegularExpression('/data-page-toolbar="1".*?data-new-scan="1"/s', $html);

        $this->assertMatchesRegularExpression('/class="[^"]*\bbg-slate-900\b[^"]*" data-confidence-band="1"/', $html);
        $this->assertStringNotContainsString('bg-gradient-to-t from-black/80', $html);
    }
}
