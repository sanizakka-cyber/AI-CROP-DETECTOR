<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 11: the mobile scan API's recoverable outcomes. Engine responses are
 * faked, so this proves the software path (storage, outcome, idempotency),
 * not diagnostic accuracy.
 */
class MobileScanOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    private function farmer(): User
    {
        return User::factory()->create(['role' => 'farmer']);
    }

    private function scan(User $user, string $kind = 'crop', array $extra = [])
    {
        return $this->withHeaders($this->apiHeaders($user))->post("/api/diagnose/{$kind}", array_merge([
            'images' => [UploadedFile::fake()->image('leaf.jpg')],
        ], $extra));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_unsupported_subject_is_stored_as_such_with_image_and_tips(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response([
                'contract_version' => '2026-10-phase11', 'assessment_outcome' => 'unsupported_subject',
                'subject_present' => false, 'image_quality' => 'good', 'image_issues' => [],
                'photo_tips' => ['Photograph the affected leaf up close'], 'alternative_findings' => [],
                'disease' => null, 'confidence' => null, 'confidence_valid' => true,
            ]),
        ]);
        $user = $this->farmer();

        $this->scan($user)->assertOk();

        $d = Diagnosis::first();
        $this->assertSame('unsupported_subject', $d->assessment_outcome);
        $this->assertSame('reviewed', $d->status);
        $this->assertNotNull($d->image_path, 'The farmer\'s photo is kept.');
        $this->assertSame('Wrong Subject', $d->statusLabel);

        cache()->forget("diagnosis:{$d->id}");
        $show = $this->withHeaders($this->apiHeaders($user))->getJson("/api/diagnose/{$d->id}");
        $show->assertJsonPath('diagnosis.assessment.outcome', 'unsupported_subject');
        $show->assertJsonPath('diagnosis.assessment.photoTips.0', 'Photograph the affected leaf up close');
    }

    public function test_engine_422_is_a_retake_photo_result_not_an_outage(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response(['detail' => ['accepted' => false, 'message' => 'This file could not be read as an image.']], 422),
        ]);
        $user = $this->farmer();

        $this->scan($user)->assertOk();

        $d = Diagnosis::first();
        $this->assertSame('poor_image', $d->assessment_outcome);
        $this->assertSame('poor_image', $d->confidence_decision);
        $this->assertNotSame('ai_unavailable', $d->confidence_decision);
        $this->assertNotNull($d->image_path);
    }

    public function test_engine_outage_keeps_the_image_and_is_marked_provider_error(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response(['detail' => ['error' => 'provider_unavailable']], 503),
        ]);
        $user = $this->farmer();

        $this->scan($user)->assertStatus(202);

        $d = Diagnosis::first();
        $this->assertSame('provider_error', $d->assessment_outcome);
        $this->assertSame('needs_review', $d->status);
        $this->assertNull($d->confidence_score);
        $this->assertNotNull($d->image_path, 'Previously the image was discarded on this path.');
    }

    public function test_retry_with_same_client_scan_id_returns_the_existing_scan(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response(['assessment_outcome' => 'assessed', 'disease' => 'Late blight', 'confidence' => 80, 'confidence_valid' => true]),
        ]);
        $user = $this->farmer();

        $first  = $this->scan($user, 'crop', ['client_scan_id' => 'scan-abc-123'])->assertOk();
        $second = $this->scan($user, 'crop', ['client_scan_id' => 'scan-abc-123'])->assertOk();

        $this->assertSame($first->json('diagnosisId'), $second->json('diagnosisId'));
        $second->assertJsonPath('duplicate', true);
        $this->assertSame(1, Diagnosis::count());
        Http::assertSentCount(2); // health ping + predict for the first call only; the retry never reaches the engine
    }

    public function test_same_client_scan_id_from_another_user_is_not_shared(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response(['assessment_outcome' => 'assessed', 'disease' => 'Healthy', 'confidence' => 90, 'confidence_valid' => true]),
        ]);

        $a = $this->scan($this->farmer(), 'crop', ['client_scan_id' => 'shared-id']);
        $b = $this->scan($this->farmer(), 'crop', ['client_scan_id' => 'shared-id']);

        $this->assertNotSame($a->json('diagnosisId'), $b->json('diagnosisId'));
        $this->assertSame(2, Diagnosis::count());
    }

    public function test_livestock_without_photo_sends_no_placeholder_image(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/livestock' => Http::response(['assessment_outcome' => 'poor_image', 'disease' => null, 'confidence' => null, 'confidence_valid' => true]),
        ]);
        $user = $this->farmer();

        $this->withHeaders($this->apiHeaders($user))->post('/api/diagnose/livestock', [
            'animalType' => 'Goat', 'assessmentType' => 'behavioral',
        ])->assertOk();

        Http::assertSent(function (HttpRequest $req) {
            if (!str_ends_with($req->url(), '/predict/livestock')) {
                return false;
            }
            foreach ($req->data() as $part) {
                if (($part['name'] ?? null) === 'images') {
                    return false;
                }
            }
            return true;
        });
        $this->assertSame('poor_image', Diagnosis::first()->assessment_outcome);
    }

    public function test_scan_forwards_the_request_locale_like_the_web_flow(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::response(['assessment_outcome' => 'assessed', 'disease' => 'Healthy', 'confidence' => 90, 'confidence_valid' => true]),
        ]);

        $this->scan($this->farmer())->assertOk();

        Http::assertSent(function (HttpRequest $req) {
            if (!str_ends_with($req->url(), '/predict/crop')) {
                return false;
            }
            foreach ($req->data() as $part) {
                if (($part['name'] ?? null) === 'language') {
                    return $part['contents'] === app()->getLocale();
                }
            }
            return false;
        });
    }

    public function test_scans_the_ai_never_answered_do_not_use_up_the_monthly_quota(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok']),
            '*/predict/crop' => Http::sequence()
                ->push(['detail' => 'down'], 503)
                ->push(['detail' => 'down'], 503)
                ->push(['assessment_outcome' => 'assessed', 'disease' => 'Healthy', 'confidence' => 90, 'confidence_valid' => true]),
        ]);
        $user = $this->farmer();
        $svc = app(\App\Services\SubscriptionLimitService::class);

        $this->scan($user)->assertStatus(202);
        $this->scan($user)->assertStatus(202);
        $this->assertSame(0, $svc->canScan($user)['used'], 'Outage records are not counted.');

        $this->scan($user)->assertOk();
        $this->assertSame(1, $svc->canScan($user)['used']);
    }
}
