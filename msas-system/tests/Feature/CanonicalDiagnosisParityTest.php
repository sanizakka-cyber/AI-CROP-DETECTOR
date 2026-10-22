<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web/APK parity against ONE shared fixture
 * (shared-fixtures/canonical-diagnosis.json, also read by
 * mobile/app/diagnosis/__tests__/canonicalParity.test.js):
 *  - the API returns the fixture's `api` block for the stored record, which
 *    is exactly what the app's parity test renders; and
 *  - the web history page and the PDF report show every `mustDisplay`
 *    string the app's test checks.
 */
class CanonicalDiagnosisParityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        return json_decode(file_get_contents(base_path('../shared-fixtures/canonical-diagnosis.json')), true);
    }

    private function seedCanonical(User $user): Diagnosis
    {
        $f = $this->fixture();
        $d = Diagnosis::create(array_merge($f['record'], ['user_id' => $user->id]));
        CollectionLocation::create(array_merge($f['location'], ['diagnosis_id' => $d->id]));
        return $d;
    }

    /** Every key in $expected must be present in $actual with an equal value (recursively). */
    private function assertSubset(array $expected, array $actual, string $path = 'diagnosis'): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, "API is missing {$path}.{$key}");
            if (is_array($value) && array_is_list($value) === false) {
                $this->assertIsArray($actual[$key], "{$path}.{$key} should be an object");
                $this->assertSubset($value, $actual[$key], "{$path}.{$key}");
            } else {
                $this->assertEquals($value, $actual[$key], "{$path}.{$key} differs from the shared fixture");
            }
        }
    }

    public function test_api_returns_exactly_what_the_app_parity_test_renders(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedCanonical($user);

        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken])
            ->getJson("/api/diagnose/{$d->id}");

        $res->assertOk();
        $this->assertSubset($this->fixture()['api'], $res->json('diagnosis'));
    }

    /** What a reader sees: tags removed, whitespace collapsed, "78 %" (number and % in separate elements) read as "78%". */
    private function visibleText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#si', ' ', $html)));
        $text = preg_replace('/\s+/u', ' ', $text);
        return preg_replace('/(\d)\s+%/', '$1%', $text);
    }

    private function lowConfidence(): array
    {
        return json_decode(file_get_contents(base_path('../shared-fixtures/low-confidence-diagnosis.json')), true);
    }

    /** Phase 12.1: the low-confidence screen, from shared-fixtures/low-confidence-diagnosis.json. */
    public function test_low_confidence_api_matches_what_the_app_renders(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = Diagnosis::create(array_merge($this->lowConfidence()['record'], ['user_id' => $user->id]));

        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken])
            ->getJson("/api/diagnose/{$d->id}");

        $res->assertOk();
        $this->assertSubset($this->lowConfidence()['api'], $res->json('diagnosis'));
    }

    public function test_low_confidence_web_history_and_report_show_what_the_app_shows(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = Diagnosis::create(array_merge($this->lowConfidence()['record'], ['user_id' => $user->id]));

        $history = $this->visibleText($this->actingAs($user)->get(route('diagnostics.history'))->assertOk()->getContent());
        $reportHtml = $this->actingAs($user)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        $report  = $this->visibleText($reportHtml);

        foreach ($this->lowConfidence()['mustDisplay'] as $text) {
            $this->assertStringContainsString($text, $history, "Web history page is missing: {$text}");
            $this->assertStringContainsString($text, $report, "Web report is missing: {$text}");
        }
        // The headline is the inconclusive one, never the leading condition.
        $this->assertStringContainsString('<div class="diag-name" data-headline="1">' . e($this->lowConfidence()['headline']) . '</div>', $reportHtml);
        $this->assertNothingTechnicalForTheFarmer($this->lowConfidence(), $history, $reportHtml);
    }

    /** Text of the PDF before its technical appendix: what the farmer reads. */
    private function farmerPartOfReport(string $reportHtml): string
    {
        $cut = strpos($reportHtml, 'data-technical-appendix');
        return $this->visibleText($cut === false ? $reportHtml : substr($reportHtml, 0, $cut));
    }

    private function assertNothingTechnicalForTheFarmer(array $fixture, string $historyText, string $reportHtml): void
    {
        foreach ($fixture['mustNotDisplay'] ?? [] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $historyText, "Farmer's web page shows \"{$word}\"");
            $this->assertStringNotContainsStringIgnoringCase($word, $this->farmerPartOfReport($reportHtml), "Farmer's part of the PDF shows \"{$word}\"");
        }
    }

    public function test_web_history_and_report_show_every_field_the_app_shows(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = $this->seedCanonical($user);

        $history = $this->visibleText($this->actingAs($user)->get(route('diagnostics.history'))->assertOk()->getContent());
        $reportHtml = $this->actingAs($user)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        $report  = $this->visibleText($reportHtml);

        foreach ($this->fixture()['mustDisplay'] as $text) {
            $this->assertStringContainsString($text, $history, "Web history page is missing: {$text}");
            $this->assertStringContainsString($text, $report, "Web report is missing: {$text}");
        }
        $this->assertNothingTechnicalForTheFarmer($this->fixture(), $history, $reportHtml);
        // The technical names are kept, in the PDF's appendix.
        $appendix = substr($reportHtml, (int) strpos($reportHtml, 'data-technical-appendix'));
        $this->assertStringContainsString('Late blight (Phytophthora infestans)', $appendix);
        $this->assertStringContainsString('Solanum lycopersicum', $appendix);
    }
}
