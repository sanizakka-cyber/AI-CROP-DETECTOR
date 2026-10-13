<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 11 web scan input: the form now uploads with XMLHttpRequest (real
 * progress). JSON responses must carry the redirect and keep the flash
 * message; a plain form post (no JS) must behave exactly as before.
 */
class WebScanUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.ai_engine.url' => '']); // no engine: stored as provider_error
    }

    private function jpeg(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('leaf.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00fake-image-bytes");
    }

    public function test_scan_page_states_the_real_limits_and_offers_all_input_controls(): void
    {
        $page = $this->actingAs(User::factory()->create(['role' => 'farmer']))->get(route('diagnostics.scan'));
        $page->assertOk();
        $page->assertSee('accept="image/jpeg,image/png,image/webp"', false);
        $page->assertSee('JPG, PNG or WebP, up to 10 MB');
        $page->assertDontSee('HEIC');
        $page->assertSee('Take Photo');
        $page->assertSee('Select Photo');
        $page->assertSee('Browse Files');
        $page->assertSee('drag &amp; drop', false);
        $page->assertSee('upload-progress-fill', false);
    }

    public function test_xhr_submission_gets_json_with_redirect_and_the_flash_survives(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $res = $this->actingAs($user)->postJson(route('diagnostics.analyze'), [
            'scan_type' => 'plant', 'image' => $this->jpeg(),
        ]);

        $res->assertOk();
        $res->assertJsonPath('redirect', route('diagnostics.history'));
        $res->assertJsonPath('diagnosis_id', Diagnosis::first()->id);
        $res->assertSessionHas('success');
    }

    public function test_plain_form_post_still_redirects(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->post(route('diagnostics.analyze'), [
            'scan_type' => 'plant', 'image' => $this->jpeg(),
        ])->assertRedirect(route('diagnostics.history'));
    }

    public function test_xhr_validation_errors_are_json_422(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant'])
            ->assertStatus(422)->assertJsonStructure(['error', 'details' => ['image']]);
    }

    public function test_xhr_quota_exhausted_is_a_readable_json_error(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        for ($i = 0; $i < 5; $i++) {
            Diagnosis::create(['user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'x', 'status' => 'reviewed', 'confidence_score' => 80]);
        }

        $res = $this->actingAs($user)->postJson(route('diagnostics.analyze'), [
            'scan_type' => 'plant', 'image' => $this->jpeg(),
        ]);

        $res->assertStatus(403);
        $this->assertStringContainsString('used all 5 AI scans', $res->json('errors.image.0'));
        $this->assertStringNotContainsString('<a', $res->json('errors.image.0'));
    }
}
