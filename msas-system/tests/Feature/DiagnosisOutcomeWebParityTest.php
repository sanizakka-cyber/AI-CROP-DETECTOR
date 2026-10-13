<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 11 web/mobile parity: the web history page, the downloadable report
 * and the mobile API all present the same outcome and the same sample
 * collection location for one stored diagnosis.
 */
class DiagnosisOutcomeWebParityTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    private function poorImageScan(User $user): Diagnosis
    {
        $d = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant', 'status' => 'reviewed',
            'disease_name' => 'Photo too unclear to assess', 'confidence_score' => null,
            'urgency_level' => null, 'confidence_decision' => 'poor_image',
            'assessment_outcome' => 'poor_image', 'image_quality' => 'poor',
            'image_issues' => ['blurred', 'too_far'],
            'photo_tips' => ['Hold the phone steady and tap to focus'],
            'contract_version' => '2026-10-phase11',
        ]);
        CollectionLocation::create([
            'diagnosis_id' => $d->id, 'country' => 'Nigeria', 'state' => 'Katsina', 'lga' => 'Funtua',
            'capture_method' => 'administrative_selection', 'differs_from_scan_location' => true,
            'verification_status' => 'unverified',
        ]);
        return $d;
    }

    public function test_history_page_shows_the_outcome_tips_and_location(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $this->poorImageScan($user);

        $page = $this->actingAs($user)->get(route('diagnostics.history'));
        $page->assertOk();
        $page->assertSee('data-outcome="poor_image"', false);
        $page->assertSee('The photo is blurred');
        $page->assertSee('Hold the phone steady and tap to focus');
        $page->assertSee('Katsina');
        $page->assertSee('Funtua');
        $page->assertSee('Scanned at a different location than collected');
    }

    public function test_downloadable_report_now_includes_outcome_and_location(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = $this->poorImageScan($user);

        $report = $this->actingAs($user)->get(route('diagnostics.report', $d));
        $report->assertOk();
        $report->assertSee('Photo could not be assessed');
        $report->assertSee('Hold the phone steady and tap to focus');
        $report->assertSee('Sample Collection Location');
        $report->assertSee('Katsina');
        $report->assertSee('Funtua');
    }

    public function test_mobile_api_returns_the_same_outcome_and_location_for_the_same_scan(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $d = $this->poorImageScan($user);

        $show = $this->withHeaders($this->apiHeaders($user))->getJson("/api/diagnose/{$d->id}");
        $show->assertOk();
        $show->assertJsonPath('diagnosis.assessment.outcome', 'poor_image');
        $show->assertJsonPath('diagnosis.assessment.imageIssues', ['blurred', 'too_far']);
        $show->assertJsonPath('diagnosis.assessment.photoTips.0', 'Hold the phone steady and tap to focus');
        $show->assertJsonPath('diagnosis.collectionLocation.state', 'Katsina');
        $show->assertJsonPath('diagnosis.collectionLocation.lga', 'Funtua');
        $show->assertJsonPath('diagnosis.collectionLocation.differsFromScanLocation', true);
        $show->assertJsonPath('diagnosis.statusLabel', 'Retake Photo');
    }

    public function test_a_scan_without_location_shows_unavailable_not_the_home_address(): void
    {
        $user = User::factory()->create(['role' => 'farmer', 'state' => 'Kano']);
        $d = Diagnosis::create([
            'user_id' => $user->id, 'type' => 'plant', 'status' => 'reviewed',
            'disease_name' => 'Late blight', 'confidence_score' => 80,
        ]);

        $this->actingAs($user)->get(route('diagnostics.report', $d))
            ->assertOk()->assertSee('No collection location was recorded for this scan.');
        $this->withHeaders($this->apiHeaders($user))->getJson("/api/diagnose/{$d->id}")
            ->assertJsonPath('diagnosis.collectionLocation', null);
    }
}
