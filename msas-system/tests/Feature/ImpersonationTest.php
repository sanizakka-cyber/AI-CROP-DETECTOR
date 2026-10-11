<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit: impersonate.start/leave were GET routes for a
 * state-changing action (logging in as another user), which bypasses
 * Laravel's CSRF protection entirely (it only covers POST/PUT/PATCH/
 * DELETE) -- any link, image tag, or link-preview fetch an authenticated
 * admin's browser loaded could silently start an impersonation session
 * with no real intent from the admin. Moved to POST; these tests confirm
 * the old GET no longer works and the existing authorization rules
 * (ceo can impersonate anyone except self; admin cannot impersonate
 * ceo/admin; no nested impersonation) still hold under POST.
 */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_impersonation_start_no_longer_accepts_get(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($ceo)->get(route('impersonate.start', $farmer));

        $response->assertStatus(405);
    }

    public function test_ceo_can_impersonate_a_farmer_via_post(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        $farmer = User::factory()->create(['role' => 'farmer', 'is_active' => true]);

        $response = $this->actingAs($ceo)->post(route('impersonate.start', $farmer));

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($farmer);
        $this->assertSame($ceo->id, session('impersonate.original_id'));
    }

    public function test_admin_cannot_impersonate_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('impersonate.start', $otherAdmin));

        $response->assertStatus(403);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_impersonate_the_ceo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ceo = User::factory()->create(['role' => 'ceo', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('impersonate.start', $ceo));

        $response->assertStatus(403);
    }

    public function test_a_farmer_cannot_impersonate_anyone(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $other = User::factory()->create(['role' => 'farmer', 'is_active' => true]);

        $response = $this->actingAs($farmer)->post(route('impersonate.start', $other));

        // RoleMiddleware redirects a farmer to their own dashboard rather
        // than a bare 403 (it has a redirect target for 'farmer') -- the
        // controller's own abort(403) is never reached either way.
        $response->assertRedirect(route('farmer.dashboard'));
        $this->assertAuthenticatedAs($farmer);
    }

    public function test_cannot_impersonate_an_inactive_account(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        $inactive = User::factory()->create(['role' => 'farmer', 'is_active' => false]);

        $response = $this->actingAs($ceo)->post(route('impersonate.start', $inactive));

        $response->assertSessionHas('error');
        $this->assertAuthenticatedAs($ceo);
    }

    public function test_nested_impersonation_is_blocked(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        // Impersonating a farmer (or any non-ceo/admin role) would already
        // be blocked one level earlier by role:ceo,admin middleware on the
        // second request -- that role check runs against whoever is
        // CURRENTLY authenticated, which during impersonation is the
        // target's own role. To actually reach the controller's nested-
        // impersonation check, the impersonated identity itself needs a
        // role the middleware admits: an admin, impersonated by the ceo.
        $adminA = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $adminB = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($ceo)->post(route('impersonate.start', $adminA));
        $response = $this->withSession(['impersonate.original_id' => $ceo->id])
            ->actingAs($adminA)->post(route('impersonate.start', $adminB));

        $response->assertSessionHas('error');
        $this->assertAuthenticatedAs($adminA);
    }

    public function test_leaving_impersonation_restores_the_original_session(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);
        $farmer = User::factory()->create(['role' => 'farmer', 'is_active' => true]);

        $this->actingAs($ceo)->post(route('impersonate.start', $farmer));
        $response = $this->withSession(['impersonate.original_id' => $ceo->id])
            ->actingAs($farmer)->post(route('impersonate.leave'));

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($ceo);
        $this->assertNull(session('impersonate.original_id'));
    }
}
