<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\MobileNotification;
use App\Models\User;
use App\Services\DiagnosisResultMapper;
use App\Support\FarmerText;
use App\Support\ResultView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The consensus result screen, from a real banana scan where it contradicted
 * itself (shared-fixtures/banana-consensus-diagnosis.json, also rendered by
 * the app's consensusScreen.test.js):
 *  - an inconclusive result is titled "Inconclusive – possible causes below",
 *    never one candidate's name, on web, PDF, API and in the notification;
 *  - its side panel says "Needs checking" and shows no severity or urgency;
 *  - normal ageing never says the plant is unhealthy;
 *  - at most three possible causes, in plain words; Latin names and jargon
 *    only in the staff technical details and the PDF appendix;
 *  - "What to do now" (2-4 steps) on every judged result, in every language;
 *  - "Retake a closer photo" with photo tips on inconclusive results;
 *  - the side panel's label and value are separate, wrapping blocks.
 */
class ConsensusResultScreenTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\FakesAiEngine;

    private const LANGS = ['en', 'ha', 'yo', 'ig', 'fr', 'ar', 'ff'];

    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('../shared-fixtures/banana-consensus-diagnosis.json')), true);
    }

    private function seedBanana(User $u, string $which): Diagnosis
    {
        return Diagnosis::create(array_merge($this->fixture()[$which]['record'], ['user_id' => $u->id]));
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private function visibleText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#si', ' ', $html)), ENT_QUOTES);
        return preg_replace('/\s+/u', ' ', $text);
    }

    /** The PDF before its technical appendix: what the farmer reads. */
    private function farmerPartOfReport(string $html): string
    {
        $cut = strpos($html, 'data-technical-appendix');
        return $cut === false ? $html : substr($html, 0, $cut);
    }

    private function assertSubset(array $expected, array $actual, string $path): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, "API is missing {$path}.{$key}");
            if (is_array($value) && !array_is_list($value) && $value !== []) {
                $this->assertIsArray($actual[$key], "{$path}.{$key} should be an object");
                $this->assertSubset($value, $actual[$key], "{$path}.{$key}");
            } else {
                $this->assertEquals($value, $actual[$key], "{$path}.{$key} differs from the shared fixture");
            }
        }
    }

    /** @return array{0: string, 1: string} web history HTML and PDF HTML */
    private function pages(User $u, Diagnosis $d, string $lang = 'en'): array
    {
        return [
            $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.history'))->assertOk()->getContent(),
            $this->actingAs($u)->withSession(['locale' => $lang])->get(route('diagnostics.report', $d))->assertOk()->getContent(),
        ];
    }

    public static function records(): array
    {
        return ['inconclusive (old wording vote)' => ['inconclusive'], 'normal ageing (label vote)' => ['normalAgeing']];
    }

    #[DataProvider('records')]
    public function test_the_api_returns_what_the_app_test_renders(string $which): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedBanana($u, $which);
        $res = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk();
        $this->assertSubset($this->fixture()[$which]['api'], $res->json('diagnosis'), 'diagnosis');
    }

    #[DataProvider('records')]
    public function test_web_and_pdf_show_what_the_app_shows_and_no_jargon(string $which): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedBanana($u, $which);
        [$history, $report] = $this->pages($u, $d);
        $farmerReport = $this->farmerPartOfReport($report);

        foreach ($this->fixture()[$which]['mustDisplay'] as $text) {
            $this->assertStringContainsString($text, $this->visibleText($history), "Web page is missing: {$text}");
            $this->assertStringContainsString($text, $this->visibleText($report), "PDF is missing: {$text}");
        }
        foreach (array_merge($this->fixture()['jargon'], $this->fixture()[$which]['mustNotDisplay'] ?? []) as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $this->visibleText($history), "Farmer's web page shows \"{$word}\"");
            $this->assertStringNotContainsStringIgnoringCase($word, $this->visibleText($farmerReport), "Farmer's part of the PDF shows \"{$word}\"");
        }
    }

    public function test_inconclusive_headline_side_panel_causes_steps_and_retake(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedBanana($u, 'inconclusive');
        [$history, $report] = $this->pages($u, $d);
        $headline = $this->fixture()['headline'];

        // Headline: never one candidate's name.
        $this->assertMatchesRegularExpression('#data-headline="1">\s*<i[^>]*></i>\s*' . preg_quote(e($headline), '#') . '\s*</h3>#u', $history);
        $this->assertStringContainsString('<div class="diag-name" data-headline="1">' . e($headline) . '</div>', $report);
        $this->assertDoesNotMatchRegularExpression('#data-headline="1">[^<]*(?:<i[^>]*></i>)?\s*Natural#u', $history . $report);

        // Side panel: Needs checking, no severity or urgency anywhere.
        foreach ([$history, $report] as $html) {
            $this->assertStringContainsString('data-health="needs_checking"', $html);
            $this->assertStringNotContainsString('data-severity=', $html);
            $this->assertStringNotContainsString('data-urgency=', $html);
        }
        $this->assertStringNotContainsString('Unhealthy', $this->visibleText($history));

        // At most three causes; 2-4 steps; the retake button with photo tips.
        $this->assertSame(3, substr_count($history, 'data-possible-cause="1"'));
        $this->assertSame(3, substr_count($report, 'data-possible-cause="1"'));
        foreach ([$history, $report] as $html) {
            $steps = substr_count($html, 'data-step="1"');
            $this->assertGreaterThanOrEqual(2, $steps);
            $this->assertLessThanOrEqual(4, $steps);
        }
        $this->assertStringContainsString('data-retake-button="1"', $history);
        $this->assertStringContainsString(route('diagnostics.scan'), $history);
        $this->assertStringContainsString('Move closer so the affected part fills most of the photo.', $history);
    }

    public function test_normal_ageing_is_healthy_with_no_severity_and_no_urgency(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedBanana($u, 'normalAgeing');
        [$history, $report] = $this->pages($u, $d);
        foreach ([$history, $report] as $html) {
            $this->assertStringContainsString('data-health="normal_ageing"', $html);
            $this->assertStringContainsString('data-severity="none"', $html);
            $this->assertStringNotContainsString('data-urgency=', $html);
            $this->assertStringNotContainsString('data-inconclusive', $html);
            $this->assertStringNotContainsString('data-retake-button', $html);
        }
        $view = $d->resultView();
        $this->assertSame('normal_ageing', $view['health']['key']);
        $this->assertSame('none', $view['severity']);
        $this->assertNull($view['urgency']);
    }

    public function test_an_older_scan_without_a_label_is_still_recognised_as_normal_ageing(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $record = $this->fixture()['normalAgeing']['record'];
        unset($record['farmer_name'], $record['condition_label'], $record['condition_category']);
        $d = Diagnosis::create(array_merge($record, ['user_id' => $u->id]));
        $this->assertSame('normal_ageing', $d->resultView()['health']['key']);
        $this->assertSame('Natural drying and shedding of old outer leaf sheaths', $d->resultView()['headline']);
    }

    public function test_every_judged_result_has_two_to_four_steps(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $cases = [
            ['type' => 'plant', 'assessment_outcome' => 'assessed', 'confidence_score' => 85, 'first_aid_steps' => null],
            ['type' => 'plant', 'assessment_outcome' => 'assessed', 'confidence_score' => 85, 'first_aid_steps' => '1) A 2) B 3) C 4) D 5) E'],
            ['type' => 'animal', 'assessment_outcome' => 'uncertain', 'confidence_score' => null],
            ['type' => 'pest', 'assessment_outcome' => 'assessed', 'confidence_score' => 50],
            ['type' => 'soil', 'assessment_outcome' => 'assessed', 'confidence_score' => 90, 'health_status' => 'Healthy'],
        ];
        foreach ($cases as $i => $over) {
            $d = Diagnosis::create(array_merge(['user_id' => $u->id, 'status' => 'reviewed', 'disease_name' => 'X',
                'image_issues' => [], 'photo_tips' => []], $over));
            $n = count($d->resultView()['steps']);
            $this->assertGreaterThanOrEqual(2, $n, "case {$i}");
            $this->assertLessThanOrEqual(4, $n, "case {$i}");
        }
        $rejected = Diagnosis::create(['user_id' => $u->id, 'type' => 'plant', 'status' => 'reviewed', 'assessment_outcome' => 'poor_image',
            'disease_name' => 'Photo too unclear to assess']);
        $this->assertSame([], $rejected->resultView()['steps']);
    }

    public function test_staff_see_the_technical_names_farmers_do_not(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $mine = $this->seedBanana($farmer, 'inconclusive');
        $theirs = $this->seedBanana($admin, 'inconclusive');

        [$farmerPage] = $this->pages($farmer, $mine);
        [$adminPage] = $this->pages($admin, $theirs);
        $this->assertStringNotContainsString('data-technical-details', $farmerPage);
        $this->assertStringContainsString('data-technical-details', $adminPage);
        $this->assertStringContainsString('Musa spp.', $adminPage);
        $this->assertStringContainsString('stem (pseudostem)', $adminPage);
        $this->assertStringContainsString('Natural leaf sheath senescence', $adminPage);

        $this->assertNull($this->api($farmer)->getJson("/api/diagnose/{$mine->id}")->json('diagnosis.technical'));
        $tech = $this->api($admin)->getJson("/api/diagnose/{$theirs->id}")->json('diagnosis.technical');
        $this->assertSame('Natural leaf sheath senescence', $tech['technical_name']);
        $this->assertSame('Musa spp.', $tech['scientific_name']);
        $this->assertCount(3, $tech['pass_findings']);

        // The PDF keeps them in its appendix, for everyone.
        [, $report] = $this->pages($farmer, $mine);
        $appendix = substr($report, (int) strpos($report, 'data-technical-appendix'));
        $this->assertStringContainsString('Musa spp.', $appendix);
        $this->assertStringContainsString('Cosmopolites sordidus', $appendix);
    }

    public function test_the_api_history_list_and_narration_use_the_same_headline(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedBanana($u, 'inconclusive');
        $row = collect($this->api($u)->getJson('/api/diagnose')->assertOk()->json('diagnoses'))->firstWhere('id', $d->id);
        $this->assertSame($this->fixture()['headline'], $row['disease_name']);
        $this->assertTrue($row['inconclusive']);
        $this->assertNull($row['severity']);
        $this->assertNull($row['urgency_level']);

        $narration = $d->narrationText();
        $this->assertStringContainsString($this->fixture()['headline'], $narration);
        $this->assertStringNotContainsString('Severity:', $narration);
        $this->assertStringNotContainsString('Urgency:', $narration);
        $this->assertStringNotContainsString('Condition: Natural', $narration);
        $this->assertStringNotContainsString('senescence', $narration);
    }

    /**
     * "DETECTED PART" ran into its value ("DETECTEDstem (pseudostem)"): label and
     * value sat side by side in a narrow column. Now each fact is a column with
     * the label (<dt>) above the value (<dd>), both allowed to wrap. The CI
     * screenshot job (result-screen-screenshots.yml) checks the rendered boxes
     * do not overlap at phone, tablet and desktop widths.
     */
    public function test_side_panel_label_and_value_are_separate_wrapping_blocks(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->seedBanana($u, 'normalAgeing');
        [$history] = $this->pages($u, Diagnosis::first());

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>' . $history);
        $xp = new \DOMXPath($doc);
        $panel = $xp->query('//dl[@data-side-panel="1"]')->item(0);
        $this->assertNotNull($panel, 'side panel missing');
        $facts = $xp->query('./div[contains(concat(" ", @class, " "), " side-fact ")]', $panel);
        $this->assertGreaterThanOrEqual(3, $facts->length);
        $snapshot = [];
        foreach ($facts as $fact) {
            $children = array_values(array_filter(iterator_to_array($fact->childNodes), fn ($n) => $n instanceof \DOMElement));
            $this->assertSame(['dt', 'dd'], array_map(fn ($n) => $n->nodeName, $children), 'label and value must be separate blocks');
            $this->assertStringContainsString('side-fact-label', $children[0]->getAttribute('class'));
            $this->assertStringContainsString('side-fact-value', $children[1]->getAttribute('class'));
            $snapshot[] = trim($children[0]->textContent) . ' = ' . preg_replace('/\s+/', ' ', trim($children[1]->textContent));
        }
        $this->assertSame(['Severity = None', 'Detected Part = stem', 'Health = Healthy (normal ageing)'], $snapshot);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.side-fact\s*\{[^}]*flex-col[^}]*min-w-0/', $css);
        $this->assertMatchesRegularExpression('/\.side-fact-value\s*\{[^}]*break-words/', $css);
        // The committed build carries the same rules (production serves public/build).
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $built = file_get_contents(public_path('build/' . $manifest['resources/css/app.css']['file']));
        $this->assertStringContainsString('.side-fact{', str_replace(' ', '', $built));
    }

    public static function langs(): array
    {
        return array_map(fn ($l) => [$l], self::LANGS);
    }

    #[DataProvider('langs')]
    public function test_every_new_farmer_string_is_in_every_web_language(string $lang): void
    {
        $strings = json_decode(file_get_contents(lang_path("{$lang}.json")), true);
        $keys = array_merge(array_values(ResultView::STEPS), array_values(ResultView::TIPS), array_values(ResultView::HEALTH),
            [ResultView::HEADLINE_INCONCLUSIVE, 'Possible causes', 'Photo tips', 'Retake a closer photo', 'What to do now', 'Tap to view the result']);
        foreach ($keys as $key) {
            $this->assertNotEmpty($strings[$key] ?? null, "{$lang}.json is missing \"{$key}\"");
        }

        $u = User::factory()->create(['role' => 'farmer', 'language' => $lang]);
        $d = $this->seedBanana($u, 'inconclusive');
        [$history] = $this->pages($u, $d, $lang);
        $text = $this->visibleText($history);
        foreach ([ResultView::HEADLINE_INCONCLUSIVE, 'What to do now', 'Retake a closer photo', ResultView::STEPS['check_rot']] as $key) {
            $this->assertStringContainsString($strings[$key], $text, "{$lang}: \"{$key}\" not shown translated");
        }
    }

    /** An end-to-end app scan, engine faked with what the new engine returns for these passes. */
    private function scanWithEngine(User $u, array $engineResult)
    {
        $this->fakeAiEngine($engineResult);
        Storage::fake('public');
        return $this->api($u)->post('/api/diagnose/crop', ['images' => [UploadedFile::fake()->image('banana.jpg')]])->assertOk();
    }

    public function test_a_scan_the_passes_agree_on_by_label_is_stored_and_announced_as_one_finding(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->scanWithEngine($u, [
            'contract_version' => '2026-10-condition-labels', 'assessment_outcome' => 'assessed',
            'disease' => 'Natural drying and shedding of old outer leaf sheaths', 'confidence' => 80,
            'condition_label' => 'normal_ageing', 'condition_category' => 'normal_ageing',
            'farmer_name' => 'Old outer layers drying naturally', 'health_status' => 'Healthy (normal ageing)',
            'severity' => 'None', 'urgency' => null, 'detected_part' => 'stem (pseudostem)',
            'consensus' => ['passes_requested' => 3, 'passes_completed' => 3, 'agreeing_passes' => 3,
                'pass_labels' => ['normal_ageing', 'normal_ageing', 'normal_ageing']],
        ]);
        $d = Diagnosis::firstOrFail();
        $this->assertSame('normal_ageing', $d->condition_label);
        $this->assertSame('Old outer layers drying naturally', $d->farmer_name);

        $body = MobileNotification::where('user_id', $u->id)->latest('id')->value('body');
        $this->assertStringStartsWith('Old outer layers drying naturally', $body);

        // The post-scan hot cache serves the same view.
        $res = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk();
        $this->assertSame('normal_ageing', $res->json('diagnosis.assessment.result.health.key'));
        $this->assertSame('stem', $res->json('diagnosis.detectedPart'));
    }

    public function test_an_inconclusive_scan_is_announced_with_the_headline_not_a_candidate(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->scanWithEngine($u, [
            'assessment_outcome' => 'uncertain', 'disease' => 'Natural leaf sheath senescence', 'confidence' => null,
            'severity' => null, 'urgency' => null,
            'alternative_findings' => [['name' => 'Fusarium wilt', 'why_considered' => 'Yellowing']],
        ]);
        $body = MobileNotification::where('user_id', $u->id)->latest('id')->value('body');
        $this->assertStringStartsWith($this->fixture()['headline'], $body);
        $this->assertStringNotContainsString('senescence', $body);
    }

    public function test_the_mapper_stores_the_label_category_and_plain_name(): void
    {
        $row = DiagnosisResultMapper::fromAiResult([
            'assessment_outcome' => 'assessed', 'disease' => 'Natural leaf sheath senescence', 'confidence' => 80,
            'condition_label' => 'normal_ageing', 'condition_category' => 'normal_ageing',
            'farmer_name' => '  Old outer layers drying naturally ',
        ]);
        $this->assertSame('normal_ageing', $row['condition_label']);
        $this->assertSame('normal_ageing', $row['condition_category']);
        $this->assertSame('Old outer layers drying naturally', $row['farmer_name']);

        $junk = DiagnosisResultMapper::fromAiResult(['condition_label' => 'DROP TABLE x', 'condition_category' => str_repeat('a', 99)]);
        $this->assertNull($junk['condition_label']);
        $this->assertNull($junk['condition_category']);
    }

    public static function plainWords(): array
    {
        return [
            'pseudostem in brackets' => ['stem (pseudostem)', 'stem'],
            'senescence'             => ['Natural leaf sheath senescence', 'Natural leaf sheath ageing'],
            'Latin binomial'         => ['Late blight (Phytophthora infestans)', 'Late blight'],
            'Latin with forma'       => ['Fusarium wilt (Fusarium oxysporum f. sp. cubense)', 'Fusarium wilt'],
            'genus spp.'             => ['Banana (Musa spp.)', 'Banana'],
            'English in brackets'    => ['Fusarium wilt (Panama disease)', 'Fusarium wilt (Panama disease)'],
            'capitalised term'       => ['Necrosis on edges', 'Dead patches on edges'],
            'lesions'                => ['dark water-soaked lesions', 'dark water-soaked spots'],
            'plain text unchanged'   => ['Old outer layers drying naturally', 'Old outer layers drying naturally'],
            'empty'                  => ['  ', null],
        ];
    }

    #[DataProvider('plainWords')]
    public function test_farmer_text_uses_plain_words(string $in, ?string $out): void
    {
        $this->assertSame($out, FarmerText::plain($in));
        if ($out !== null) {
            $this->assertFalse(FarmerText::hasJargon($out), $out);
        }
    }
}
