<?php

namespace Tests\Unit;

use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_accumulates_instead_of_resetting(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 3) as $_) {
            SubscriptionUsage::track($user->id, 'vet_consultations_per_cycle');
        }
        SubscriptionUsage::track($user->id, 'reports_generated', 2);

        $this->assertSame(3, SubscriptionUsage::getCount($user->id, 'vet_consultations_per_cycle'));
        $this->assertSame(2, SubscriptionUsage::getCount($user->id, 'reports_generated'));
        $this->assertSame(1, SubscriptionUsage::where('user_id', $user->id)->where('feature_key', 'vet_consultations_per_cycle')->count());
    }

    public function test_limit_is_reached_after_enough_uses(): void
    {
        $user = User::factory()->create();
        $sub = new Subscription(['user_id' => $user->id, 'plan' => 'basic_pro']);
        $limit = $sub->getLimit('vet_consultations_per_cycle');
        $this->assertSame(3, $limit, 'Basic Pro allows 3 vet consultations');
        foreach (range(1, $limit) as $_) {
            $this->assertFalse($sub->hasReachedLimit('vet_consultations_per_cycle'));
            SubscriptionUsage::track($user->id, 'vet_consultations_per_cycle');
        }
        $this->assertTrue($sub->hasReachedLimit('vet_consultations_per_cycle'));
    }
}
