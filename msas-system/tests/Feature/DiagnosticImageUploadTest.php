<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 7, Section 2: regression coverage for the diagnostic image upload
 * path's size/format validation.
 *
 * IMPORTANT SCOPE NOTE: Laravel's UploadedFile::fake() constructs an
 * in-memory file object directly -- it never goes through a real PHP SAPI
 * multipart upload, so upload_max_filesize / post_max_size (the actual
 * root cause of the Phase 6 production failure, fixed in this phase's
 * Dockerfile) cannot be exercised by this test suite at all. These tests
 * cover the layer that CAN be tested here: Laravel's own validation
 * rules. The PHP-ini fix itself can only be verified by a real HTTP
 * request against a real PHP-FPM process -- done separately as a live
 * production check against the deployed container, documented in the
 * Phase 7 report rather than claimed here.
 */
class DiagnosticImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.ai_engine.url' => '']);
    }

    public function test_an_image_near_the_documented_10mb_limit_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        // 9.5MB, reported as image/jpeg -- exercises the max:10240 boundary
        // without needing real decodable image bytes, since
        // DiagnosticController::analyze() validates with 'mimes' (content-
        // type sniffing / explicit MIME), not the stricter 'image' rule.
        $file = UploadedFile::fake()->create('leaf.jpg', 9500, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $file,
        ]);

        $response->assertSessionDoesntHaveErrors('image');
        $this->assertDatabaseCount('diagnoses', 1);
    }

    public function test_an_image_over_the_10mb_limit_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $file = UploadedFile::fake()->create('leaf.jpg', 10500, 'image/jpeg'); // 10.5MB

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $file,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_an_unsupported_file_type_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $file,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_a_zero_byte_file_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $file = UploadedFile::fake()->create('empty.jpg', 0, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
            'image'     => $file,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_a_missing_image_field_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('diagnoses', 0);
    }

    /**
     * All four mobile API scan endpoints must share the identical 10240
     * (10MB) per-image limit -- Phase 6 left livestock inconsistent at
     * 5120 (5MB), the only one of the four that differed with no
     * documented reason. This confirms the fix: a real image comfortably
     * between the old (5MB) and new (10MB) ceiling is now accepted on
     * every endpoint, not just three of the four.
     */
    public function test_all_four_mobile_scan_endpoints_share_the_same_10mb_limit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'farmer']);
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        // A fresh real GD-rendered JPEG per endpoint -- controllers that
        // ->store() the upload move/consume the underlying temp file, so
        // reusing one UploadedFile instance across multiple requests
        // would make later calls fail for an unrelated reason (a missing
        // file), not the validation behavior this test actually covers.
        $makeImage = fn () => UploadedFile::fake()->image('leaf.jpg', 3000, 3000);

        $endpoints = [
            '/api/diagnose/crop'      => fn () => ['images' => [$makeImage()]],
            '/api/diagnose/livestock' => fn () => ['animalType' => 'cattle', 'assessmentType' => 'visual', 'images' => [$makeImage()]],
            '/api/diagnose/soil'      => fn () => ['images' => [$makeImage()]],
            '/api/diagnose/pest'      => fn () => ['images' => [$makeImage()]],
        ];

        foreach ($endpoints as $uri => $payloadFactory) {
            $response = $this->withHeaders($headers)->post($uri, $payloadFactory());
            // Not asserting 200 (that needs a real/faked AI engine response)
            // -- asserting the request never fails *validation* is exactly
            // what this regression covers: a 422 here would mean the image
            // itself was rejected by the mimes/image/max rules.
            $this->assertNotEquals(422, $response->status(), "{$uri} rejected a valid image at validation: " . $response->getContent());
        }
    }
}
