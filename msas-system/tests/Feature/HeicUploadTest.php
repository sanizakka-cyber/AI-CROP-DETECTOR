<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use App\Services\HeicConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 12.1: any HEIC/HEIF scan upload (app API or web form, e.g. iPhone
 * Safari sending HEIC as-is) is converted to JPEG on the server before
 * validation, storage and the AI call. The fixture is a real HEIC file.
 */
class HeicUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); // app uploads
        Storage::fake('local');  // web uploads (private disk)
        config(['services.ai_engine.url' => 'http://engine.test', 'services.heic.driver' => 'engine']);
    }

    private function heic(string $name = 'IMG_0042.HEIC'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(base_path('tests/fixtures/leaf.heic')));
    }

    private function jpegBytes(): string
    {
        $im = imagecreatetruecolor(240, 160);
        imagefill($im, 0, 0, imagecolorallocate($im, 60, 140, 60));
        ob_start();
        imagejpeg($im, null, 90);
        return ob_get_clean();
    }

    private function fakeEngine(int $convertStatus = 200): void
    {
        $jpeg = $this->jpegBytes();
        Http::fake([
            'http://engine.test/convert/image' => $convertStatus === 200
                ? Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])
                : Http::response(['detail' => ['message' => 'unreadable']], $convertStatus),
            'http://engine.test/health' => Http::response(['status' => 'ok']),
            'http://engine.test/predict/*' => Http::response([
                'contract_version' => '2026-10-phase11', 'assessment_outcome' => 'assessed', 'subject_present' => true,
                'image_quality' => 'good', 'image_issues' => [], 'photo_tips' => [], 'alternative_findings' => [],
                'disease' => 'Late blight', 'confidence' => 80, 'confidence_valid' => true,
            ]),
        ]);
    }

    private function api(User $u)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken]);
    }

    private function sentToPredict(): ?HttpRequest
    {
        return Http::recorded(fn ($r) => str_contains($r->url(), '/predict/'))->first()[0] ?? null;
    }

    public function test_the_fixture_is_a_real_heic_and_is_detected(): void
    {
        $this->assertTrue(app(HeicConverter::class)->isHeic($this->heic()));
        $this->assertTrue(app(HeicConverter::class)->isHeic($this->heic('renamed.jpg')), 'detected by content, not name');
        $this->assertFalse(app(HeicConverter::class)->isHeic(UploadedFile::fake()->createWithContent('leaf.jpg', $this->jpegBytes())));
    }

    public function test_an_app_heic_upload_is_converted_before_the_scan(): void
    {
        $this->fakeEngine();
        $u = User::factory()->create(['role' => 'farmer']);

        $this->api($u)->post('/api/diagnose/crop', ['images' => [$this->heic()]])->assertOk();

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/convert/image'));
        $predict = $this->sentToPredict();
        $this->assertNotNull($predict, 'the scan reached the AI engine');
        $d = Diagnosis::sole();
        $this->assertSame('Late blight', $d->disease_name);
        $stored = Storage::disk('public')->get($d->image_path);
        $this->assertSame("\xFF\xD8\xFF", substr($stored, 0, 3), 'the stored photo is a JPEG, viewable in any browser');
    }

    public function test_a_heic_renamed_to_jpg_is_still_converted(): void
    {
        $this->fakeEngine();
        $u = User::factory()->create(['role' => 'farmer']);
        $this->api($u)->post('/api/diagnose/crop', ['images' => [$this->heic('photo.jpg')]])->assertOk();
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/convert/image'));
    }

    public function test_a_web_heic_upload_from_safari_is_converted(): void
    {
        $this->fakeEngine();
        $u = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($u)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant', 'image' => $this->heic()])
            ->assertOk()->assertJsonPath('diagnosis_id', Diagnosis::sole()->id);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/convert/image'));
        // The web form stores photos on the private 'local' disk.
        $this->assertSame("\xFF\xD8\xFF", substr(Storage::disk('local')->get(Diagnosis::sole()->image_path), 0, 3));
    }

    public function test_a_photo_that_cannot_be_converted_gets_a_clear_message_and_no_scan(): void
    {
        $this->fakeEngine(422);
        $u = User::factory()->create(['role' => 'farmer']);

        $this->api($u)->post('/api/diagnose/crop', ['images' => [$this->heic()]], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('details.images.0', 'This photo could not be converted. Please choose a JPEG or PNG photo.');
        $this->assertSame(0, Diagnosis::count());
        $this->assertNull($this->sentToPredict());

        $this->actingAs($u)->postJson(route('diagnostics.analyze'), ['scan_type' => 'plant', 'image' => $this->heic()])
            ->assertStatus(422)->assertJsonStructure(['error', 'details' => ['image']]);
    }

    public function test_jpeg_uploads_are_not_touched(): void
    {
        $this->fakeEngine();
        $u = User::factory()->create(['role' => 'farmer']);
        $this->api($u)->post('/api/diagnose/crop', ['images' => [UploadedFile::fake()->createWithContent('leaf.jpg', $this->jpegBytes())]])->assertOk();
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/convert/image'));
    }

    public function test_imagick_converts_when_it_supports_heic(): void
    {
        if (!extension_loaded('imagick') || empty(\Imagick::queryFormats('HEIC'))) {
            $this->markTestSkipped('Imagick with HEIC support is not installed here; the engine driver is used.');
        }
        config(['services.heic.driver' => 'auto']);
        $jpeg = app(HeicConverter::class)->toJpeg($this->heic());
        $this->assertSame("\xFF\xD8\xFF", substr(file_get_contents($jpeg->getRealPath()), 0, 3));
    }
}
