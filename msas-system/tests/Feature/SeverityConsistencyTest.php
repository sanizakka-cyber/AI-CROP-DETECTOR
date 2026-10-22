<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use App\Services\DiagnosisResultMapper;
use App\Support\Severity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 12.2: one severity value per scan.
 *
 * The app's header badge said "HIGH" while its details said "Moderate" for
 * the same scan: the API's aiResult.severity was filled from urgency_level.
 * severity_level is now the only stored severity, normalised on write, and
 * every place that shows a severity reads it. The main test collects the
 * severity from every surface (API detail, API history, web history, PDF,
 * narration) and fails if any two disagree, or if any shows the urgency.
 */
class SeverityConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private function scan(User $u, array $over = []): Diagnosis
    {
        return Diagnosis::create(array_merge([
            'user_id' => $u->id, 'type' => 'plant', 'status' => 'reviewed', 'language' => 'en',
            'subject_name' => 'Tomato', 'disease_name' => 'Late blight', 'confidence_score' => 82,
            'assessment_outcome' => 'assessed', 'image_quality' => 'good', 'image_issues' => [], 'photo_tips' => [],
        ], $over));
    }

    /** Every severity shown for scan $d, keyed by surface. */
    private function severitiesShown(User $u, Diagnosis $d): array
    {
        cache()->forget("diagnosis:v3:{$d->id}");
        $detail = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk();
        $list = collect($this->api($u)->getJson('/api/diagnose')->assertOk()->json('diagnoses'))->firstWhere('id', $d->id);
        $history = $this->actingAs($u)->get(route('diagnostics.history'))->assertOk()->getContent();
        $report = $this->actingAs($u)->get(route('diagnostics.report', $d))->assertOk()->getContent();
        preg_match_all('/data-severity="([^"]*)"/', $history, $h);
        preg_match_all('/data-severity="([^"]*)"/', $report, $r);
        preg_match('/Severity: (\w+)\./', $d->narrationText(), $n);

        return [
            'api detail severity'      => $detail->json('diagnosis.aiResult.severity'),
            'api detail severityLevel' => Severity::key($detail->json('diagnosis.aiResult.severityLevel')),
            'api history severity'     => $list['severity'] ?? null,
            'api history severity_level' => Severity::key($list['severity_level'] ?? null),
            'web history badge'        => $h[1][0] ?? null,
            'pdf badge'                => $r[1][0] ?? null,
            'narration'                => Severity::key($n[1] ?? null),
        ];
    }

    public static function cases(): array
    {
        // [stored severity, stored urgency, expected key]
        return [
            'the reported bug: severity Moderate, urgency High' => ['Moderate', 'High', 'moderate'],
            'mild, emergency'                                   => ['Mild', 'Emergency', 'mild'],
            'severe, low'                                       => ['Severe', 'Low', 'severe'],
            'critical, medium'                                  => ['Critical', 'Medium', 'critical'],
            'lowercase from an engine'                          => ['moderate', 'high', 'moderate'],
            'legacy synonym'                                    => ['High', 'Medium', 'severe'],
        ];
    }

    #[DataProvider('cases')]
    public function test_every_surface_shows_the_same_severity_and_never_the_urgency(string $sev, string $urg, string $key): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, ['severity_level' => $sev, 'urgency_level' => $urg]);

        $shown = $this->severitiesShown($u, $d);
        $this->assertCount(1, array_unique($shown), 'Severity disagrees between surfaces: ' . json_encode($shown));
        $this->assertSame($key, reset($shown));

        // Urgency is still there, separately and labelled as urgency.
        $detail = $this->api($u)->getJson("/api/diagnose/{$d->id}")->json('diagnosis.aiResult');
        $this->assertSame(strtolower($urg), $detail['urgency']);
        $this->assertStringContainsString('data-urgency="' . strtolower($urg) . '"',
            $this->actingAs($u)->get(route('diagnostics.report', $d))->getContent());
    }

    public function test_the_api_returns_the_shared_fixture_the_app_test_renders(): void
    {
        $fixture = json_decode(file_get_contents(base_path('../shared-fixtures/severity-diagnosis.json')), true);
        $u = User::factory()->create(['role' => 'farmer']);
        $d = Diagnosis::create(array_merge($fixture['record'], ['user_id' => $u->id]));
        $ai = $this->api($u)->getJson("/api/diagnose/{$d->id}")->assertOk()->json('diagnosis.aiResult');
        foreach ($fixture['api']['aiResult'] as $key => $want) {
            $this->assertEquals($want, $ai[$key] ?? null, "aiResult.{$key}");
        }
        $row = collect($this->api($u)->getJson('/api/diagnose')->json('diagnoses'))->firstWhere('id', $d->id);
        foreach (['severity_level', 'severity', 'urgency_level'] as $key) {
            $this->assertSame($fixture['historyRow'][$key], $row[$key], "history.{$key}");
        }
    }

    public function test_no_severity_is_shown_anywhere_when_the_result_has_none(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $d = $this->scan($u, ['severity_level' => null, 'urgency_level' => 'High']);
        $shown = array_filter($this->severitiesShown($u, $d), fn ($v) => $v !== null && $v !== '');
        $this->assertSame([], $shown, 'A severity appeared for a scan without one: ' . json_encode($shown));
    }

    public function test_severity_is_derived_once_on_write_whatever_the_input(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        foreach (['Moderate' => 'Moderate', 'SEVERE' => 'Severe', ' mild ' => 'Mild', 'not_applicable' => null, 'banana' => null] as $in => $want) {
            $this->assertSame($want, $this->scan($u, ['severity_level' => $in])->fresh()->severity_level, "input {$in}");
        }
        $mapped = DiagnosisResultMapper::fromAiResult(['severity' => 'moderate', 'urgency' => 'High', 'confidence' => 80, 'confidence_valid' => true]);
        $this->assertSame('Moderate', $mapped['severity_level']);
        $this->assertSame('High', $mapped['urgency_level']);
    }

    public function test_stored_rows_are_recomputed_from_the_model_reply_and_can_be_restored(): void
    {
        $migration = require database_path('migrations/2026_10_18_000002_recompute_diagnosis_severity.php');
        $migration->down();
        $u = User::factory()->create(['role' => 'farmer']);
        // Raw inserts, bypassing the model's normaliser, as old rows were written.
        $rows = [
            'json'      => ['severity_level' => 'High', 'raw_model_output' => '{"assessment_outcome":"assessed","severity":"moderate","urgency":"high"}', 'assessment_outcome' => 'assessed'],
            'truncated' => ['severity_level' => 'Severe', 'raw_model_output' => '{"assessment_outcome":"assessed","confidence":70,"severity":"mild","urgency":"low","sympt', 'assessment_outcome' => 'assessed'],
            'legacy'    => ['severity_level' => 'Moderate', 'raw_model_output' => "disease | Late blight\nseverity | Severe\nurgency | High", 'assessment_outcome' => null],
            'invented'  => ['severity_level' => 'Moderate', 'raw_model_output' => "disease | Late blight\nurgency | High", 'assessment_outcome' => null],
            'no_finding'=> ['severity_level' => 'Moderate', 'raw_model_output' => '{"assessment_outcome":"poor_image","severity":"moderate"}', 'assessment_outcome' => 'poor_image'],
            'no_raw'    => ['severity_level' => 'moderate', 'raw_model_output' => null, 'assessment_outcome' => null],
        ];
        $ids = [];
        foreach ($rows as $name => $r) {
            $ids[$name] = DB::table('diagnoses')->insertGetId(array_merge([
                'user_id' => $u->id, 'type' => 'plant', 'status' => 'reviewed', 'disease_name' => 'Late blight',
                'created_at' => now(), 'updated_at' => now(),
            ], $r));
        }

        $migration->up();
        $now = fn ($n) => DB::table('diagnoses')->where('id', $ids[$n])->value('severity_level');
        $this->assertSame('Moderate', $now('json'));
        $this->assertSame('Mild', $now('truncated'));
        $this->assertSame('Severe', $now('legacy'));
        $this->assertNull($now('invented'));
        $this->assertNull($now('no_finding'));
        $this->assertSame('Moderate', $now('no_raw'));

        $migration->down();
        foreach ($rows as $name => $r) {
            $this->assertSame($r['severity_level'], $now($name), "{$name} was not restored");
        }
        $migration->up(); // leave the schema as the other tests expect
    }
}
