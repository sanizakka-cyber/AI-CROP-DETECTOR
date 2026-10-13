<?php

namespace Tests\Feature;

use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /api/analytics/outbreaks: field roles get regional, anonymised trends of
 * real findings only; staff get national figures; other roles get 403.
 */
class OutbreaksAccessTest extends TestCase
{
    use RefreshDatabase;

    private function headers(User $u): array
    {
        return ['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken];
    }

    private function scan(string $disease, ?string $state, array $extra = []): void
    {
        $d = Diagnosis::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'farmer'])->id, 'type' => 'plant',
            'disease_name' => $disease, 'status' => 'reviewed', 'confidence_score' => 80,
            'assessment_outcome' => 'assessed',
        ], $extra));
        if ($state) {
            CollectionLocation::create(['diagnosis_id' => $d->id, 'country' => 'Nigeria', 'state' => $state, 'verification_status' => 'unverified']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan('Late blight', 'Katsina');
        $this->scan('Late blight', 'Katsina');
        $this->scan('Maize streak', 'Katsina');                         // only 1 case in region
        $this->scan('Fall armyworm damage', 'Kano');
        $this->scan('Fall armyworm damage', 'Kano');                    // other state
        $this->scan('Photo too unclear to assess', 'Katsina', ['assessment_outcome' => 'poor_image', 'confidence_score' => null]);
        $this->scan('Photo too unclear to assess', 'Katsina', ['assessment_outcome' => 'poor_image', 'confidence_score' => null]);
        $this->scan('Healthy, no disease detected', 'Katsina');
        $this->scan('Healthy, no disease detected', 'Katsina');
        $this->scan('Pending Expert Review', 'Katsina', ['assessment_outcome' => 'provider_error', 'confidence_decision' => 'ai_unavailable', 'confidence_score' => null]);
        $this->scan('Pending Expert Review', 'Katsina', ['assessment_outcome' => 'provider_error', 'confidence_decision' => 'ai_unavailable', 'confidence_score' => null]);
    }

    public function test_farmer_sees_only_real_findings_in_their_state_with_at_least_two_cases(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'state' => 'Katsina']);

        $res = $this->withHeaders($this->headers($farmer))->getJson('/api/analytics/outbreaks');

        $res->assertOk()->assertJsonPath('region', 'Katsina');
        $names = collect($res->json('outbreaks'))->pluck('disease')->all();
        $this->assertSame(['Late blight'], $names);
        $res->assertJsonPath('outbreaks.0.count', 2);
        $res->assertJsonPath('outbreaks.0.region', 'Katsina');
    }

    public function test_vet_gets_the_same_regional_view(): void
    {
        $vet = User::factory()->create(['role' => 'vet', 'state' => 'Kano']);

        $res = $this->withHeaders($this->headers($vet))->getJson('/api/analytics/outbreaks');

        $res->assertOk();
        $this->assertSame(['Fall armyworm damage'], collect($res->json('outbreaks'))->pluck('disease')->all());
    }

    public function test_admin_sees_national_figures_including_single_cases(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $res = $this->withHeaders($this->headers($admin))->getJson('/api/analytics/outbreaks');

        $res->assertOk()->assertJsonPath('region', 'Nigeria');
        $names = collect($res->json('outbreaks'))->pluck('disease')->sort()->values()->all();
        $this->assertSame(['Fall armyworm damage', 'Late blight', 'Maize streak'], $names);
    }

    public function test_other_roles_are_still_forbidden(): void
    {
        $dealer = User::factory()->create(['role' => 'dealer']);
        $this->withHeaders($this->headers($dealer))->getJson('/api/analytics/outbreaks')->assertStatus(403);
    }
}
