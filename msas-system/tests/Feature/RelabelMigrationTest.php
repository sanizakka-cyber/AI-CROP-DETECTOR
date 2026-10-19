<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 2026_10_19_000002_relabel_scans_the_ai_never_judged: changes only "photo
 * too unclear" rows the AI never judged, keeps every old value in
 * scan_relabel_backfill, and down() puts each row back exactly.
 */
class RelabelMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = ['assessment_outcome', 'confidence_decision', 'disease_name', 'photo_tips', 'image_quality', 'urgency_level'];

    private function migration()
    {
        return require database_path('migrations/2026_10_19_000002_relabel_scans_the_ai_never_judged.php');
    }

    private function row(int $id): array
    {
        return (array) DB::table('diagnoses')->where('id', $id)->first(self::COLUMNS);
    }

    public function test_relabels_only_unjudged_rows_backs_them_up_and_reverses(): void
    {
        $user = User::factory()->create();
        $base = ['user_id' => $user->id, 'type' => 'plant', 'status' => 'reviewed', 'assessment_outcome' => 'poor_image',
                 'confidence_decision' => 'poor_image', 'disease_name' => 'Photo too unclear', 'urgency_level' => 'Low',
                 'image_quality' => 'poor', 'photo_tips' => ['Move closer']];
        $never = Diagnosis::create($base + ['explanation' => 'Engine returned 401']);
        $notPlant = Diagnosis::create($base + ['explanation' => 'The image does not appear to show a plant.']);
        $judged = Diagnosis::create($base + ['explanation' => 'Too blurred to judge', 'ai_model_name' => 'claude-sonnet-5']);
        $other = Diagnosis::create(array_merge($base, ['assessment_outcome' => 'assessed', 'confidence_decision' => 'accepted', 'confidence_score' => 80]));
        $before = array_map(fn ($d) => $this->row($d->id), [$never, $notPlant, $judged, $other]);

        Schema::dropIfExists('scan_relabel_backfill');
        $this->migration()->up();

        $this->assertSame('provider_error', $this->row($never->id)['assessment_outcome']);
        $this->assertSame('ai_unavailable', $this->row($never->id)['confidence_decision']);
        $this->assertNull($this->row($never->id)['urgency_level']);
        $this->assertTrue($never->fresh()->is_failed_scan);
        $this->assertSame('unsupported_subject', $this->row($notPlant->id)['assessment_outcome']);
        $this->assertSame($before[2], $this->row($judged->id), 'a row the AI judged is untouched');
        $this->assertSame($before[3], $this->row($other->id), 'other outcomes are untouched');

        $backups = DB::table('scan_relabel_backfill')->orderBy('diagnosis_id')->get();
        $this->assertSame([$never->id, $notPlant->id], $backups->pluck('diagnosis_id')->map(fn ($v) => (int) $v)->all());
        $this->assertEquals($before[0], json_decode($backups[0]->old_values, true));

        $this->migration()->down();

        foreach ([$never, $notPlant, $judged, $other] as $i => $d) {
            $this->assertEquals($before[$i], $this->row($d->id), "row {$d->id} restored");
        }
        $this->assertFalse(Schema::hasTable('scan_relabel_backfill'));
    }
}
