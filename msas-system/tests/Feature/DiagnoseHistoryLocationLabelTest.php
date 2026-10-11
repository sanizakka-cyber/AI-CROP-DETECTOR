<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 4 mobile audit: the scan history list
 * (mobile/app/(tabs)/records.jsx) showed no location information at all,
 * despite the single-diagnosis detail endpoint having a full location
 * section -- because Api\DiagnoseApiController::history() never sent any
 * location data in the first place. Adds a lightweight label per row.
 */
class DiagnoseHistoryLocationLabelTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_history_includes_a_location_label_when_a_location_was_recorded(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create(['user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'Test Leaf Spot']);
        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'state' => 'Katsina', 'lga' => 'Funtua']);

        $response = $this->withHeaders($this->apiHeaders($user))->getJson('/api/diagnose');

        $response->assertOk();
        $this->assertSame('Funtua, Katsina', $response->json('data.0.location_label'));
    }

    public function test_history_location_label_is_null_when_no_location_was_recorded(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        Diagnosis::create(['user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'Test Leaf Spot']);

        $response = $this->withHeaders($this->apiHeaders($user))->getJson('/api/diagnose');

        $response->assertOk();
        $this->assertNull($response->json('data.0.location_label'));
    }

    public function test_history_location_label_falls_back_when_location_has_no_state_or_lga(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $diagnosis = Diagnosis::create(['user_id' => $user->id, 'type' => 'plant', 'disease_name' => 'Test Leaf Spot']);
        CollectionLocation::create(['diagnosis_id' => $diagnosis->id, 'latitude' => 12.98, 'longitude' => 7.6]);

        $response = $this->withHeaders($this->apiHeaders($user))->getJson('/api/diagnose');

        $response->assertOk();
        $this->assertSame('Location recorded', $response->json('data.0.location_label'));
    }
}
