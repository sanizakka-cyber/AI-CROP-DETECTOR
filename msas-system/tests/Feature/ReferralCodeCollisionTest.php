<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit: referral_code is generated with
 * strtoupper(Str::random(8)) and has a DB-level unique constraint
 * (2026_07_28_700003_add_referral_nps_to_users.php) -- a collision can't
 * silently create a duplicate, but nothing previously retried on one, so
 * the unlucky user who did collide would see an uncaught 500 instead of
 * simply getting a different code. Uses Str::createRandomStringsUsing()
 * to force a real collision deterministically rather than relying on
 * chance.
 */
class ReferralCodeCollisionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();
        parent::tearDown();
    }

    public function test_a_referral_code_collision_is_retried_not_a_500(): void
    {
        $existing = User::factory()->create(['referral_code' => 'ABCDEFGH']);
        $user = User::factory()->create(['referral_code' => null]);

        // First call collides with $existing's code; the second call
        // (inside the retry loop) produces a genuinely free one.
        $calls = 0;
        Str::createRandomStringsUsing(function () use (&$calls) {
            $calls++;
            return $calls === 1 ? 'abcdefgh' : 'zzzzzzzz';
        });

        $response = $this->actingAs($user)->get(route('referral.index'));

        $response->assertOk();
        $this->assertSame('ZZZZZZZZ', $user->refresh()->referral_code);
        $this->assertSame('ABCDEFGH', $existing->refresh()->referral_code, 'The existing code must be untouched.');
    }
}
