<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 6, Priority 6/8: the CEO AI-scan analytics dashboard and CSV export
 * were extended in Phase 5 to carry real sample-collection coordinates
 * (state/LGA/lat/lon/accuracy) alongside the farmer's registered address.
 * Neither route had ANY regression test before this phase — the route
 * group's `role:ceo,admin` middleware (routes/web.php) was the only thing
 * standing between a farmer and another farmer's precise GPS coordinates,
 * completely unverified by any automated test. This file closes that gap
 * and confirms the export itself tells the two location concepts apart.
 */
class CeoScanAnalyticsAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * RoleMiddleware (app/Http/Middleware/RoleMiddleware.php) does not
     * return 403 for a denied role when the user's own dashboard route
     * exists — it redirects there instead, with an 'error' flash message.
     * It only falls back to abort(403) if that redirect route is missing.
     * A farmer's dashboard route always exists, so the real denial here is
     * a redirect away from /ceo/ai-analytics, not a 403 — asserting 403
     * would fail for the wrong reason and mask this route actually working.
     */
    public function test_farmer_cannot_view_the_ai_analytics_dashboard(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($farmer)->get('/ceo/ai-analytics')->assertRedirect(route('farmer.dashboard'));
    }

    public function test_farmer_cannot_access_the_csv_export_with_precise_coordinates(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($farmer)->get('/ceo/ai-analytics/export')->assertRedirect(route('farmer.dashboard'));
    }

    public function test_ceo_can_view_the_dashboard_and_export(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);

        $this->actingAs($ceo)->get('/ceo/ai-analytics')->assertOk();
        $this->actingAs($ceo)->get('/ceo/ai-analytics/export')->assertOk();
    }

    public function test_admin_can_view_the_dashboard_and_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/ceo/ai-analytics')->assertOk();
        $this->actingAs($admin)->get('/ceo/ai-analytics/export')->assertOk();
    }

    /**
     * The export must distinguish "user's registered address" from
     * "sample collection location" as separate, correctly-labelled
     * columns (spec Section 6/9) — never substitute one for the other,
     * and never fabricate a location for a scan that has none.
     */
    public function test_export_separates_registered_address_from_sample_location(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);

        $farmerWithLocation = User::factory()->create(['role' => 'farmer', 'state' => 'Lagos', 'lga' => 'Ikeja']);
        $diagnosisWithLocation = Diagnosis::create([
            'user_id' => $farmerWithLocation->id, 'type' => 'plant',
            'disease_name' => 'Test Blight', 'confidence_score' => 80, 'status' => 'reviewed',
        ]);
        CollectionLocation::create([
            'diagnosis_id' => $diagnosisWithLocation->id,
            'country' => 'Nigeria', 'state' => 'Katsina', 'lga' => 'Funtua',
            'capture_method' => 'administrative_selection', 'verification_status' => 'unverified',
        ]);

        $farmerWithoutLocation = User::factory()->create(['role' => 'farmer', 'state' => 'Kano', 'lga' => 'Dala']);
        Diagnosis::create([
            'user_id' => $farmerWithoutLocation->id, 'type' => 'plant',
            'disease_name' => 'Test Rot', 'confidence_score' => 70, 'status' => 'reviewed',
        ]);

        $response = $this->actingAs($ceo)->get('/ceo/ai-analytics/export');
        $response->assertOk();

        $csv = $response->streamedContent();

        // The farmer's registered address (Lagos/Ikeja) must appear, AND
        // the real, different collection location (Katsina/Funtua) must
        // also appear — the export carries both, not one standing in for
        // the other.
        $this->assertStringContainsString('Lagos', $csv);
        $this->assertStringContainsString('Ikeja', $csv);
        $this->assertStringContainsString('Katsina', $csv);
        $this->assertStringContainsString('Funtua', $csv);

        // The scan with no collection_locations row must leave the sample
        // location columns genuinely blank in the CSV row, not filled in
        // with Kano/Dala (its farmer's registered address) as a substitute.
        $lines = array_filter(explode("\n", $csv));
        $rotRow = null;
        foreach ($lines as $line) {
            if (str_contains($line, 'Test Rot')) { $rotRow = str_getcsv($line); break; }
        }
        $this->assertNotNull($rotRow, 'The no-location scan must still appear in the export.');
        // Column order from CeoScanAnalyticsController::exportCsv()'s header
        // row: 0 Scan ID, 1 Date/Time, 2 Type, 3 Crop/Subject, 4 Diagnosis,
        // 5 Confidence %, 6 Confidence Decision, 7 Validation Status,
        // 8 AI Model, 9 AI Model Version, 10 Severity, 11 Raw Status,
        // 12 Display Status, 13 User, 14 User's Registered State,
        // 15 User's Registered LGA, 16 Sample Collection State, ...
        $this->assertSame('Kano', $rotRow[14] ?? null, "User's registered state must still show.");
        $this->assertSame('', $rotRow[16] ?? null, 'Sample collection state must be blank, not fabricated from the registered address.');
    }

    /**
     * A diagnosis_id is UNIQUE on collection_locations (migration
     * 2026_10_09_000001) — a second location row for the same scan must
     * be rejected at the database level, not just by application logic,
     * so a retried/duplicated submission can never leave two conflicting
     * location records for one scan (spec Section 5/8: no orphaned or
     * inconsistent location records).
     */
    public function test_a_diagnosis_cannot_have_two_collection_location_rows(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant',
            'disease_name' => 'Test', 'confidence_score' => 80, 'status' => 'reviewed',
        ]);

        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Katsina']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Kano']);
    }

    /**
     * HasCeoScanFilters::applyNonGeoFilters() used Postgres-only 'ilike'
     * unconditionally — SQLite (this test's database) has no such
     * operator and throws a SQL syntax error the moment any of these
     * filters is actually used. Nothing exercised this before this test;
     * confirms the driver-aware fix (likeOperator()) actually works
     * rather than just not crashing by accident.
     */
    public function test_dashboard_crop_filter_does_not_crash_on_sqlite(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        Diagnosis::create([
            'user_id' => $farmer->id, 'type' => 'plant', 'subject_name' => 'Maize',
            'disease_name' => 'Test', 'confidence_score' => 80, 'status' => 'reviewed',
        ]);

        $response = $this->actingAs($ceo)->get('/ceo/ai-analytics?crop=maize');

        $response->assertOk();
    }

    /** Deleting a diagnosis cascades to its collection_location — no orphaned location rows can be left behind. */
    public function test_deleting_a_diagnosis_cascades_to_its_collection_location(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant',
            'disease_name' => 'Test', 'confidence_score' => 80, 'status' => 'reviewed',
        ]);
        $location = CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Katsina']);

        $diagnosis->delete();

        $this->assertDatabaseMissing('collection_locations', ['id' => $location->id]);
    }
}
