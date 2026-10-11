<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit: VetController::respond()/startVideo() (web)
 * and Api\ConsultationApiController::vetRespond()/vetStartVideo() (mobile)
 * never re-checked that the responding expert's role matched the
 * consultation's case_type (unlike show()/queue(), which do), and only
 * excluded status === 'resolved' rather than requiring status === 'open'
 * -- a consultation still awaiting payment ('awaiting_payment') could be
 * resolved for free by any verified vet/agronomist who reached the route
 * with its ID. Both gaps existed identically in both the web and mobile
 * controllers; these tests cover both.
 */
class ConsultationRespondAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function expert(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'is_verified' => true]);
        $user->startTrial('professional_starter');
        return $user;
    }

    private function consultation(array $overrides = []): Consultation
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        return Consultation::create(array_merge([
            'farmer_id' => $farmer->id, 'case_type' => 'livestock',
            'symptoms' => 'Test symptoms.', 'status' => 'open',
        ], $overrides));
    }

    // ── Web (VetController) ─────────────────────────────────────────────────

    public function test_a_vet_cannot_respond_to_a_crop_case(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'crop']);

        $response = $this->actingAs($vet)->post(route('vet.respond', $consultation), ['expert_response' => 'Some advice here.']);

        $response->assertStatus(403);
        $this->assertNull($consultation->refresh()->expert_response);
    }

    public function test_an_agronomist_cannot_respond_to_a_livestock_case(): void
    {
        $agronomist = $this->expert('agronomist');
        $consultation = $this->consultation(['case_type' => 'livestock']);

        $response = $this->actingAs($agronomist)->post(route('vet.respond', $consultation), ['expert_response' => 'Some advice here.']);

        $response->assertStatus(403);
    }

    public function test_a_vet_cannot_respond_to_a_consultation_still_awaiting_payment(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'awaiting_payment']);

        $response = $this->actingAs($vet)->post(route('vet.respond', $consultation), ['expert_response' => 'Some advice here.']);

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->expert_response, 'An unpaid consultation must not be resolvable for free.');
    }

    public function test_a_vet_can_respond_to_an_open_livestock_case(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'open']);

        $response = $this->actingAs($vet)->post(route('vet.respond', $consultation), ['expert_response' => 'Give the animal electrolytes.']);

        $response->assertRedirect(route('vet.queue'));
        $this->assertSame('resolved', $consultation->refresh()->status);
    }

    public function test_a_vet_cannot_start_video_on_a_crop_case(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'crop']);

        $response = $this->actingAs($vet)->post(route('vet.start-video', $consultation));

        $response->assertStatus(403);
        $this->assertNull($consultation->refresh()->video_room_id);
    }

    public function test_a_vet_cannot_start_video_on_an_unpaid_consultation(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'awaiting_payment']);

        $response = $this->actingAs($vet)->post(route('vet.start-video', $consultation));

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->video_room_id);
    }

    // ── Mobile API (Api\ConsultationApiController) ──────────────────────────

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_api_a_vet_cannot_respond_to_a_crop_case(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'crop']);

        $response = $this->withHeaders($this->apiHeaders($vet))
            ->postJson("/api/vet/consultations/{$consultation->id}/respond", ['expert_response' => 'Some advice here.']);

        $response->assertStatus(403);
    }

    public function test_api_a_vet_cannot_respond_to_an_unpaid_consultation(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'awaiting_payment']);

        $response = $this->withHeaders($this->apiHeaders($vet))
            ->postJson("/api/vet/consultations/{$consultation->id}/respond", ['expert_response' => 'Some advice here.']);

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->expert_response);
    }

    public function test_api_a_vet_can_respond_to_an_open_livestock_case(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'open']);

        $response = $this->withHeaders($this->apiHeaders($vet))
            ->postJson("/api/vet/consultations/{$consultation->id}/respond", ['expert_response' => 'Give the animal electrolytes.']);

        $response->assertOk();
        $this->assertSame('resolved', $consultation->refresh()->status);
    }

    public function test_api_a_vet_cannot_start_video_on_an_unpaid_consultation(): void
    {
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'livestock', 'status' => 'awaiting_payment']);

        $response = $this->withHeaders($this->apiHeaders($vet))
            ->postJson("/api/vet/consultations/{$consultation->id}/start-video");

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->video_room_id);
    }

    // ── Admin assignment role re-validation ──────────────────────────────────

    public function test_admin_cannot_assign_a_farmer_as_the_expert_on_a_consultation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $farmer = User::factory()->create(['role' => 'farmer']);
        $consultation = $this->consultation(['case_type' => 'livestock']);

        $response = $this->actingAs($admin)->post(route('admin.consultations.assign', $consultation), ['expert_id' => $farmer->id]);

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->expert_id);
    }

    public function test_admin_cannot_assign_a_vet_to_a_crop_case(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vet = $this->expert('vet');
        $consultation = $this->consultation(['case_type' => 'crop']);

        $response = $this->actingAs($admin)->post(route('admin.consultations.assign', $consultation), ['expert_id' => $vet->id]);

        $response->assertStatus(422);
        $this->assertNull($consultation->refresh()->expert_id);
    }

    public function test_admin_can_assign_a_matching_expert(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agronomist = $this->expert('agronomist');
        $consultation = $this->consultation(['case_type' => 'crop']);

        $response = $this->actingAs($admin)->post(route('admin.consultations.assign', $consultation), ['expert_id' => $agronomist->id]);

        $response->assertRedirect();
        $this->assertSame($agronomist->id, $consultation->refresh()->expert_id);
    }
}
