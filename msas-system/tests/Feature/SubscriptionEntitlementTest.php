<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Found in live testing: a farmer on an active Basic trial was limited to
 * the Free plan's 5 scans (the lookup matched status='active' only), and a
 * paid plan that had already ended kept its limits.
 */
class SubscriptionEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function sub(User $u, array $attrs): void
    {
        Subscription::create(array_merge(['user_id' => $u->id, 'plan' => 'basic', 'billing_cycle' => 'monthly'], $attrs));
    }

    private function usedScans(User $u, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Diagnosis::create(['user_id' => $u->id, 'type' => 'plant', 'disease_name' => 'x', 'status' => 'reviewed', 'confidence_score' => 80]);
        }
    }

    public function test_an_active_trial_gets_its_plan_limits_not_free(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->sub($u, ['status' => 'trial', 'trial_ends_at' => now()->addDays(10), 'starts_at' => now(), 'ends_at' => now()->addDays(10)]);
        $this->usedScans($u, 5);

        $check = app(SubscriptionLimitService::class)->canScan($u);

        $this->assertSame(config('subscription.plans.basic.limits.ai_scans_per_month'), $check['limit']);
        $this->assertTrue($check['allowed'], 'A Basic trial user must not stop at the Free plan\'s 5 scans.');
        $this->assertSame('basic', $check['plan']);
    }

    public function test_an_expired_trial_falls_back_to_free(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->sub($u, ['status' => 'trial', 'trial_ends_at' => now()->subDay(), 'ends_at' => now()->subDay()]);

        $this->assertSame('free', app(SubscriptionLimitService::class)->canScan($u)['plan']);
    }

    public function test_a_paid_plan_that_has_ended_no_longer_grants_its_limits(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->sub($u, ['status' => 'active', 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDay()]);

        $this->assertSame('free', app(SubscriptionLimitService::class)->canScan($u)['plan']);
    }

    public function test_a_current_paid_plan_still_gets_its_limits(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->sub($u, ['status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        $this->assertSame('basic', app(SubscriptionLimitService::class)->canScan($u)['plan']);
    }

    public function test_trial_users_can_book_consultations_their_plan_includes(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->sub($u, ['status' => 'trial', 'trial_ends_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)]);

        $this->assertNotSame('free', app(SubscriptionLimitService::class)->canConsult($u, 'vet')['plan']);
    }
}
