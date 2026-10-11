<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 7, Section 9: collection_locations.diagnosis_id is both a unique
 * index and a foreign key with cascadeOnDelete() (2026_10_09_000001_add_
 * scan_geolocation_and_ai_provenance.php:67) -- correct by migration
 * inspection, but nothing in the app ever actually deletes a Diagnosis
 * (no admin delete route, no soft deletes, no observer), so the cascade
 * had never been exercised by any test. This runs it for real against
 * Postgres specifically: SQLite enforces foreign keys only when
 * `PRAGMA foreign_keys = ON` is set per-connection, which this app's test
 * config does not do, so a cascade that only "works" on SQLite by
 * Eloquent's own cascading delete hooks (which this app doesn't use --
 * plain Diagnosis::delete() with no onDelete listener) would not prove
 * the actual DB-level constraint does anything at all.
 */
class DiagnosisLocationCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires a real PostgreSQL connection to prove the DB-level cascade, not an SQLite-specific behavior.');
        }
    }

    public function test_deleting_a_diagnosis_cascades_to_its_collection_location_on_real_postgres(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'Test Leaf Spot',
        ]);
        $location = CollectionLocation::create([
            'diagnosis_id' => $diagnosis->id, 'state' => 'Katsina', 'lga' => 'Funtua',
        ]);

        $this->assertDatabaseHas('collection_locations', ['id' => $location->id]);

        // Raw DB::table delete, not Diagnosis::delete() -- proves the
        // constraint itself cascades, independent of any Eloquent-level
        // deletion hook the app might or might not have.
        DB::table('diagnoses')->where('id', $diagnosis->id)->delete();

        $this->assertDatabaseMissing('collection_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('diagnoses', ['id' => $diagnosis->id]);
    }

    public function test_a_diagnosis_cannot_have_more_than_one_collection_location_row(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'Test Leaf Spot',
        ]);
        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Katsina']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Kano']);
    }
}
