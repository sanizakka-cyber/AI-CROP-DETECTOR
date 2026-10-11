<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 security/functional audit: expertAccept()/expertDecline()
 * (Admin\ConsultationController) already correctly checked
 * abort_if($consultation->expert_id !== auth()->id()) -- the intended
 * caller is the assigned vet/agronomist, never an admin/ceo -- but the
 * route was gated by role:admin,ceo with no UI anywhere pointing at it.
 * An admin/ceo always fails that check (they're never the assigned
 * expert), so literally nobody could reach these actions. Moved to the
 * vet/agronomist route group (same controller, same logic -- only the
 * route's authorization was wrong) and wired into resources/views/vet/show.blade.php.
 */
class ConsultationExpertAcceptDeclineTest extends TestCase
{
    use RefreshDatabase;

    /** The vet/agronomist route group requires an active subscription; factory users have none by default. */
    private function expert(string $role = 'vet'): User
    {
        $user = User::factory()->create(['role' => $role]);
        $user->startTrial('professional_starter');
        return $user;
    }

    private function consultation(User $expert, string $consultantStatus = 'pending_acceptance'): Consultation
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        return Consultation::create([
            'farmer_id' => $farmer->id, 'expert_id' => $expert->id, 'case_type' => 'livestock',
            'symptoms' => 'Test symptoms.', 'status' => 'open', 'consultant_status' => $consultantStatus,
        ]);
    }

    public function test_the_assigned_vet_can_accept_their_own_consultation(): void
    {
        $vet = $this->expert();
        $consultation = $this->consultation($vet);

        $response = $this->actingAs($vet)->post(route('vet.consultation.accept', $consultation));

        $response->assertRedirect();
        $this->assertSame('accepted', $consultation->refresh()->consultant_status);
        $this->assertNotNull($consultation->consultant_accepted_at);
    }

    public function test_the_assigned_vet_can_decline_and_the_case_returns_to_unassigned(): void
    {
        $vet = $this->expert();
        $consultation = $this->consultation($vet);

        $response = $this->actingAs($vet)->post(route('vet.consultation.decline', $consultation), [
            'reason' => 'Outside my specialization.',
        ]);

        $response->assertRedirect();
        $consultation->refresh();
        $this->assertNull($consultation->expert_id);
        $this->assertNull($consultation->consultant_status);
        $this->assertSame('Outside my specialization.', $consultation->consultant_decline_reason);
    }

    public function test_a_different_vet_cannot_accept_a_case_assigned_to_someone_else(): void
    {
        $assignedVet = $this->expert();
        $otherVet    = $this->expert();
        $consultation = $this->consultation($assignedVet);

        $response = $this->actingAs($otherVet)->post(route('vet.consultation.accept', $consultation));

        $response->assertStatus(403);
        $this->assertSame('pending_acceptance', $consultation->refresh()->consultant_status);
    }

    public function test_an_admin_cannot_accept_a_consultation_as_if_they_were_the_assigned_expert(): void
    {
        $vet = $this->expert();
        $consultation = $this->consultation($vet);
        $admin = User::factory()->create(['role' => 'admin']);

        // The old admin-only route no longer exists; confirm admin/ceo
        // genuinely has no path to act as the expert on someone else's case.
        $response = $this->actingAs($admin)->post(route('vet.consultation.accept', $consultation));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertSame('pending_acceptance', $consultation->refresh()->consultant_status);
    }
}
