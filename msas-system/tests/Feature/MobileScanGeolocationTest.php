<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mobile/web parity coverage (spec Section 12's "Web/mobile parity
 * assessment" + end-to-end acceptance item 11: "Repeat the workflow on
 * supported mobile and web clients"). Confirms the mobile
 * DiagnoseApiController captures the same location + confidence-provenance
 * data as the web DiagnosticController, via the same shared
 * CollectionLocation::createFromRequest() / DiagnosisResultMapper code.
 */
class MobileScanGeolocationTest extends TestCase
{
    use RefreshDatabase;

    /** Mirrors SecurityRegressionTest::apiHeaders() — this app's custom bearer-token auth, not Sanctum. */
    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_mobile_crop_scan_stores_location_and_confidence_provenance(): void
    {
        Storage::fake('public');

        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
            '*/predict/crop' => Http::response([
                'ai_model'                  => 'claude-sonnet-5',
                'ai_model_version'          => 'claude-sonnet-5',
                'raw_model_output'          => 'subject_name | Tomato\nconfidence | 88',
                'confidence_interpretation' => 'Self-reported certainty, not a calibrated probability.',
                'validation_status'         => 'unvalidated',
                'subject_name'              => 'Tomato',
                'disease'                   => 'Early Blight',
                'confidence'                => 88,
                'confidence_valid'          => true,
                'health_status'             => 'Diseased',
                'scan_type'                 => 'crop',
            ], 200),
        ]);

        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($user))->post('/api/diagnose/crop', [
            'images'       => [UploadedFile::fake()->image('leaf.jpg')],
            'loc_state'    => 'Kano',
            'loc_lga'      => 'Dala',
            'loc_latitude' => '12.0022',
            'loc_longitude'=> '8.5920',
            'loc_capture_method' => 'gps_device',
        ]);

        $response->assertOk();

        $diagnosis = Diagnosis::first();
        $this->assertNotNull($diagnosis);
        $this->assertSame(88.0, $diagnosis->confidence_score);
        $this->assertSame('claude-sonnet-5', $diagnosis->ai_model_name);
        $this->assertSame('sufficient', $diagnosis->confidence_decision);

        $location = CollectionLocation::where('diagnosis_id', $diagnosis->id)->first();
        $this->assertNotNull($location, 'Mobile scan must persist the same location data the web scan flow does.');
        $this->assertSame('Kano', $location->state);
        $this->assertSame('gps_device', $location->capture_method);

        // show() must expose both blocks to the mobile client.
        $show = $this->withHeaders($this->apiHeaders($user))->getJson("/api/diagnose/{$diagnosis->id}");
        $show->assertOk();
        $show->assertJsonPath('diagnosis.collectionLocation.state', 'Kano');
        $show->assertJsonPath('diagnosis.confidenceProvenance.aiModelName', 'claude-sonnet-5');
        $show->assertJsonPath('diagnosis.confidenceProvenance.validationStatus', 'unvalidated');
    }

    public function test_mobile_scan_without_location_fields_still_succeeds(): void
    {
        Storage::fake('public');

        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
            '*/predict/crop' => Http::response([
                'subject_name' => 'Maize', 'disease' => 'Healthy', 'confidence' => 91, 'confidence_valid' => true,
            ], 200),
        ]);

        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($user))->post('/api/diagnose/crop', [
            'images' => [UploadedFile::fake()->image('leaf.jpg')],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('collection_locations', 0);
    }

    public function test_user_cannot_see_another_users_diagnosis_via_show(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
            '*/predict/crop' => Http::response(['subject_name' => 'Maize', 'disease' => 'Healthy', 'confidence' => 91, 'confidence_valid' => true], 200),
        ]);

        $owner = User::factory()->create(['role' => 'farmer']);
        $other = User::factory()->create(['role' => 'farmer']);

        $this->withHeaders($this->apiHeaders($owner))->post('/api/diagnose/crop', [
            'images' => [UploadedFile::fake()->image('leaf.jpg')],
        ]);
        $diagnosis = Diagnosis::first();

        // Bypass the 24h cache (which keys only on ID, not owner) to force the DB-fallback ownership check.
        cache()->forget("diagnosis:{$diagnosis->id}");

        $response = $this->withHeaders($this->apiHeaders($other))->getJson("/api/diagnose/{$diagnosis->id}");
        $response->assertStatus(404);
    }
}
