<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end coverage for the sample-collection-location capture added to
 * the web scan flow (spec Section 5 / 12's "Location tests") plus the
 * confidence-provenance fields (spec Section 4.5) on the resulting
 * Diagnosis row.
 *
 * AI_ENGINE_URL is deliberately cleared in setUp() so every scan here
 * exercises the real, deterministic "AI engine unavailable" fallback path
 * (DiagnosisResultMapper::aiUnavailableFallback()) instead of attempting a
 * real network call — the point of these tests is the location/provenance
 * behaviour, not the AI engine's own HTTP contract (covered separately by
 * the mapper's unit tests against a synthetic AI response).
 */
class ScanGeolocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.ai_engine.url' => '']);
    }

    private function fakeJpeg(string $name = 'leaf.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes"
        );
    }

    public function test_scan_without_any_location_fields_still_succeeds(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $this->fakeJpeg(),
        ]);

        $response->assertRedirect(route('diagnostics.history'));
        $this->assertDatabaseCount('diagnoses', 1);
        $this->assertDatabaseCount('collection_locations', 0);
    }

    public function test_scan_with_full_location_fields_creates_linked_location_row(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type'          => 'plant',
            'image'              => $this->fakeJpeg(),
            'loc_country'        => 'Nigeria',
            'loc_state'          => 'Katsina',
            'loc_lga'            => 'Funtua',
            'loc_community'      => 'Funtua town',
            'loc_latitude'       => '11.1965',
            'loc_longitude'      => '7.3167',
            'loc_accuracy_meters'=> '12',
            'loc_capture_method' => 'gps_device',
            'loc_user_confirmed' => '1',
        ]);

        $response->assertRedirect(route('diagnostics.history'));

        $diagnosis = Diagnosis::first();
        $location  = CollectionLocation::where('diagnosis_id', $diagnosis->id)->first();

        $this->assertNotNull($location, 'A fully-specified location must be persisted.');
        $this->assertSame('Katsina', $location->state);
        $this->assertSame('Funtua', $location->lga);
        $this->assertEqualsWithDelta(11.1965, $location->latitude, 0.0001);
        $this->assertSame('gps_device', $location->capture_method);
        $this->assertTrue($location->user_confirmed);
        $this->assertSame('unverified', $location->verification_status, 'A plausible in-range Nigerian coordinate is not flagged.');
    }

    public function test_coordinates_outside_global_range_are_rejected_by_validation(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type'     => 'plant',
            'image'         => $this->fakeJpeg(),
            'loc_latitude'  => '999',
            'loc_longitude' => '7.0',
        ]);

        $response->assertSessionHasErrors('loc_latitude');
        $this->assertDatabaseCount('diagnoses', 0, 'An invalid coordinate must block the request, not silently drop the bad value and still scan.');
    }

    public function test_coordinates_outside_nigeria_are_flagged_but_not_blocked(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        // Valid globally, implausible for Nigeria (London).
        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type'     => 'plant',
            'image'         => $this->fakeJpeg(),
            'loc_latitude'  => '51.5074',
            'loc_longitude' => '-0.1278',
        ]);

        $response->assertRedirect(route('diagnostics.history'));

        $location = CollectionLocation::first();
        $this->assertNotNull($location);
        $this->assertSame('boundary_mismatch_flagged', $location->verification_status);
        // The coordinate itself is kept, not silently dropped — a flag, not a rejection.
        $this->assertNotNull($location->latitude);
    }

    public function test_differs_from_scan_location_flag_is_recorded(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type'             => 'plant',
            'image'                 => $this->fakeJpeg(),
            'loc_state'             => 'Katsina',
            'loc_differs_from_scan' => '1',
        ]);

        $location = CollectionLocation::first();
        $this->assertNotNull($location);
        $this->assertTrue($location->differs_from_scan_location);
    }

    public function test_ai_unavailable_scan_still_has_honest_confidence_provenance(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $this->fakeJpeg(),
        ]);

        $diagnosis = Diagnosis::first();
        $this->assertNull($diagnosis->confidence_score);
        $this->assertSame('needs_review', $diagnosis->status);
        $this->assertSame('ai_unavailable', $diagnosis->confidence_decision);
        $this->assertSame('unvalidated', $diagnosis->validation_status);
        $this->assertNull($diagnosis->generated_by);
    }

    /**
     * Section 12's "cross-user access" security test, specific to the new
     * location data: a diagnosis image/report route is already
     * ownership-gated — confirms that gate still holds with the new
     * collection_locations join in place (no accidental new leak path).
     */
    public function test_user_cannot_view_another_users_diagnosis_image(): void
    {
        $owner   = User::factory()->create(['role' => 'farmer']);
        $other   = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($owner)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $this->fakeJpeg(),
            'loc_state' => 'Katsina',
        ]);
        $diagnosis = Diagnosis::first();

        $response = $this->actingAs($other)->get(route('diagnostics.image', $diagnosis));
        $response->assertForbidden();
    }

    /**
     * A diagnosis created before this migration (no collection_locations
     * row, provenance columns all NULL) must keep rendering history
     * without error — the exact "historical record handling" acceptance
     * criterion from spec Section 12.
     */
    public function test_historical_diagnosis_without_new_columns_renders_safely(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $diagnosis = Diagnosis::create([
            'user_id'          => $user->id,
            'type'             => 'plant',
            'disease_name'     => 'Legacy record',
            'confidence_score' => 72.5,
            'status'           => 'reviewed',
        ]);

        $this->assertNull($diagnosis->collectionLocation);
        $this->assertSame('location_unverified', $diagnosis->dataQualityStatus);

        $response = $this->actingAs($user)->get(route('diagnostics.history'));
        $response->assertOk();
        $response->assertSee('Legacy record');
    }
}
