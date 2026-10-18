<?php

namespace Tests\Feature;

use App\Models\TrainingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 12.2: the upload + labelling page for partner photos
 * (/admin/training-images). Consent or an open licence is required, GPS
 * and other EXIF data are removed, photos are private, admins review, and
 * the export is a manifest ai-engine/eval/run_eval.py can read.
 */
class TrainingImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['training_images.disk' => 'local']);
        Storage::fake('local');
    }

    /** A real JPEG with an EXIF block carrying a GPS tag, like a phone photo. */
    private function phonePhoto(string $name = 'leaf.jpg', int $seed = 1): UploadedFile
    {
        $im = imagecreatetruecolor(400, 300);
        imagefill($im, 0, 0, imagecolorallocate($im, 90 + $seed, 140, 60));
        ob_start();
        imagejpeg($im, null, 90);
        $jpeg = ob_get_clean();
        $exif = "Exif\0\0" . 'MM' . "\0*" . "GPSLatitude 12.99N GPSLongitude 7.60E";
        $app1 = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;
        $path = tempnam(sys_get_temp_dir(), 'ph') . '.jpg';
        file_put_contents($path, substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2));
        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    private function fieldUpload(array $over = [], array $files = null): array
    {
        return array_merge([
            'images' => $files ?? [$this->phonePhoto()],
            'mode' => 'livestock', 'species' => 'cattle', 'condition_label' => 'Lumpy skin disease',
            'synonyms' => 'LSD', 'label_basis' => 'vet_diagnosis', 'state' => 'Katsina', 'lga' => 'Dutsin-Ma',
            'source_type' => 'field_partner', 'licence' => 'partner-consent',
            'consent_reference' => 'KT-2026-001', 'consent_confirmed' => '1',
        ], $over);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_farmers_cannot_use_the_page(): void
    {
        // RoleMiddleware sends other roles back to their own dashboard.
        $this->actingAs($this->user('farmer'))->get(route('admin.training-images.index'))->assertRedirect(route('farmer.dashboard'));
        $this->assertSame(0, TrainingImage::count());
    }

    public function test_a_vet_uploads_a_field_photo_and_its_gps_data_is_removed(): void
    {
        $vet = $this->user('vet');
        $photo = $this->phonePhoto();
        $this->assertStringContainsString('GPSLatitude', file_get_contents($photo->getRealPath()));

        $this->actingAs($vet)->post(route('admin.training-images.store'), $this->fieldUpload([], [$photo]))
            ->assertRedirect(route('admin.training-images.index'));

        $img = TrainingImage::sole();
        $this->assertSame('pending', $img->review_status);
        $this->assertSame($vet->id, $img->uploaded_by);
        $this->assertTrue($img->consent_confirmed);
        $stored = Storage::disk('local')->get($img->image_path);
        $this->assertStringNotContainsString('GPS', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
        $this->assertSame(hash('sha256', $stored), $img->sha256);
    }

    public function test_a_field_photo_without_written_consent_is_refused(): void
    {
        $this->actingAs($this->user('extension-officer'))
            ->post(route('admin.training-images.store'), $this->fieldUpload(['consent_confirmed' => null, 'consent_reference' => null]))
            ->assertSessionHasErrors(['consent_confirmed', 'consent_reference']);
        $this->assertSame(0, TrainingImage::count());
    }

    public function test_dataset_photos_need_an_open_licence_and_their_source(): void
    {
        $vet = $this->user('vet');
        $base = $this->fieldUpload(['source_type' => 'public_dataset', 'consent_reference' => null, 'consent_confirmed' => null]);
        $this->actingAs($vet)->post(route('admin.training-images.store'), array_merge($base, ['licence' => 'CC-BY-NC-4.0', 'source_name' => 'X', 'source_url' => 'https://example.org']))
            ->assertSessionHasErrors('licence');
        $this->actingAs($vet)->post(route('admin.training-images.store'), array_merge($base, ['licence' => 'CC-BY-4.0']))
            ->assertSessionHasErrors(['source_name', 'source_url']);
        $this->actingAs($vet)->post(route('admin.training-images.store'), array_merge($base, ['licence' => 'partner-consent', 'source_name' => 'X', 'source_url' => 'https://example.org']))
            ->assertSessionHasErrors('licence');
        $this->actingAs($vet)->post(route('admin.training-images.store'), array_merge($base, [
            'licence' => 'CC-BY-4.0', 'source_name' => 'Lumpy Skin Images Dataset', 'source_url' => 'https://data.mendeley.com/datasets/w36hpf86j2/1',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('CC-BY-4.0', TrainingImage::sole()->licence);
    }

    public function test_the_same_photo_is_not_added_twice(): void
    {
        $vet = $this->user('vet');
        $this->actingAs($vet)->post(route('admin.training-images.store'), $this->fieldUpload([], [$this->phonePhoto('a.jpg', 1)]));
        $this->actingAs($vet)->post(route('admin.training-images.store'), $this->fieldUpload([], [$this->phonePhoto('b.jpg', 1), $this->phonePhoto('c.jpg', 2)]))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 photo(s) added') && str_contains($m, '1 already in the set'));
        $this->assertSame(2, TrainingImage::count());
    }

    public function test_partners_see_only_their_own_photos_and_cannot_review_or_export(): void
    {
        $vet = $this->user('vet');
        $other = $this->user('vet');
        $this->actingAs($other)->post(route('admin.training-images.store'), $this->fieldUpload());
        $img = TrainingImage::sole();

        $page = $this->actingAs($vet)->get(route('admin.training-images.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-training-image="' . $img->id . '"', $page);
        $this->actingAs($vet)->get(route('admin.training-images.image', $img))->assertStatus(403);
        $this->actingAs($vet)->post(route('admin.training-images.review', $img), ['decision' => 'approved'])->assertStatus(403);
        $this->actingAs($vet)->get(route('admin.training-images.export'))->assertStatus(403);
        $this->actingAs($other)->get(route('admin.training-images.image', $img))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_admin_approves_and_exports_a_manifest_the_eval_runner_reads(): void
    {
        $vet = $this->user('vet');
        $admin = $this->user('admin');
        $this->actingAs($vet)->post(route('admin.training-images.store'), $this->fieldUpload([], [$this->phonePhoto('a.jpg', 1)]));
        $this->actingAs($vet)->post(route('admin.training-images.store'), $this->fieldUpload(['label_basis' => 'suspected', 'condition_label' => 'Mange'], [$this->phonePhoto('b.jpg', 2)]));
        foreach (TrainingImage::all() as $img) {
            $this->actingAs($admin)->post(route('admin.training-images.review', $img), ['decision' => 'approved'])->assertRedirect();
        }

        $res = $this->actingAs($admin)->get(route('admin.training-images.export'))->assertOk();
        $zip = new \ZipArchive();
        $zip->open($res->baseResponse->getFile()->getPathname());
        $csv = array_map('str_getcsv', array_filter(explode("\n", $zip->getFromName('manifest.csv'))));
        $this->assertSame(['image', 'mode', 'expected', 'synonyms', 'expected_outcome', 'licence', 'source', 'label_basis'], $csv[0]);
        $this->assertCount(2, $csv, 'only the vet-confirmed photo; "suspected" labels are not used for accuracy');
        $this->assertSame(['livestock', 'Lumpy skin disease', 'LSD'], array_slice($csv[1], 1, 3));
        $this->assertNotFalse($zip->getFromName($csv[1][0]), 'the image is in the zip at the manifest path');
    }

    public function test_withdrawn_consent_deletes_the_photo(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($this->user('vet'))->post(route('admin.training-images.store'), $this->fieldUpload());
        $img = TrainingImage::sole();
        $this->actingAs($admin)->post(route('admin.training-images.review', $img), ['decision' => 'withdrawn']);
        $this->assertFalse(Storage::disk('local')->exists($img->image_path));
        $this->assertSame('withdrawn', $img->fresh()->review_status);
        $this->actingAs($admin)->get(route('admin.training-images.image', $img))->assertStatus(410);
    }

    public function test_reviewers_are_warned_when_photos_would_be_lost_on_redeploy(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.training-images.index'))
            ->assertOk()->assertSee('data-local-disk-warning="1"', false);
    }
}
