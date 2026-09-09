<?php

namespace Tests\Feature;

use App\Models\FarmRecord;
use App\Models\MobileNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the vulnerabilities found and fixed during the
 * Phase 1 + Phase 2 security audits (see the companion Security Audit
 * report). Each test is named after the defect it guards against, not the
 * happy path — the goal is that any of these regressing turns this suite
 * red, not that the feature works in general.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * This app does not use Laravel Sanctum for mobile/API auth despite the
     * package being installed — App\Http\Middleware\ApiAuthenticate checks a
     * bearer token against a SHA-256 hash stored directly on users.api_token
     * (see User::createToken()), a fully custom mechanism. Sanctum::actingAs()
     * simulates the wrong auth system entirely and fails outright (User
     * doesn't have Sanctum's HasApiTokens trait). This mirrors the real
     * production login flow instead.
     */
    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    // ── SEC-003: notification delete must not fake success on a foreign ID ─────

    public function test_deleting_another_users_notification_returns_404_not_success(): void
    {
        $owner   = User::factory()->create();
        $attacker = User::factory()->create();

        $notification = MobileNotification::create([
            'user_id' => $owner->id,
            'title'   => 'Test notification',
            'body'    => 'Body text',
        ]);

        $response = $this->withHeaders($this->apiHeaders($attacker))
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
        $this->assertNotNull(
            $notification->fresh(),
            'Notification must still exist — the foreign-ID delete must be a genuine no-op, not a real deletion.'
        );
    }

    public function test_deleting_own_notification_actually_succeeds(): void
    {
        $owner = User::factory()->create();

        $notification = MobileNotification::create([
            'user_id' => $owner->id,
            'title'   => 'Test notification',
            'body'    => 'Body text',
        ]);

        $this->withHeaders($this->apiHeaders($owner))
            ->deleteJson("/api/notifications/{$notification->id}")->assertOk();
        $this->assertNull(MobileNotification::find($notification->id));
    }

    // ── SEC-004: cross-user IDOR on farms (representative of the wider sweep) ──

    public function test_farmer_cannot_read_another_farmers_farm(): void
    {
        $owner    = User::factory()->create(['role' => 'farmer']);
        $attacker = User::factory()->create(['role' => 'farmer']);

        $farm = FarmRecord::create([
            'user_id'   => $owner->id,
            'crop_type' => 'Maize',
        ]);

        $headers = $this->apiHeaders($attacker);

        $this->withHeaders($headers)->getJson("/api/farms/{$farm->id}")->assertStatus(404);
        $this->withHeaders($headers)->putJson("/api/farms/{$farm->id}", ['notes' => 'hacked'])->assertStatus(404);
        $this->withHeaders($headers)->deleteJson("/api/farms/{$farm->id}")->assertStatus(404);

        $this->assertSame('Maize', $farm->fresh()->crop_type, 'Attacker must not be able to mutate another user\'s farm.');
    }

    // ── SEC-005: vertical escalation — a farmer role must never reach CEO-only routes ──

    public function test_farmer_is_blocked_from_ceo_only_route(): void
    {
        // /ceo/monitoring is a session-guarded web route (not the custom
        // bearer-token API), so Laravel's own actingAs() is correct here.
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($farmer)->get('/ceo/monitoring');

        // RoleMiddleware deliberately redirects an unauthorized web user to
        // their own dashboard (see its $redirectMap) rather than a bare 403
        // when one exists for their role -- the real guarantee to test is
        // that they never reach the CEO page, not a specific status code.
        $response->assertRedirect(route('farmer.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_profile_update_cannot_be_used_to_self_promote_role(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $this->withHeaders($this->apiHeaders($farmer))
            ->patchJson('/api/auth/profile', ['role' => 'ceo'])->assertOk();

        $this->assertSame('farmer', $farmer->fresh()->role, 'role must not be mass-assignable through the profile endpoint.');
    }

    // ── SEC-001: AI endpoints must be rate limited (real cost per call) ────────
    //
    // CAUTION: AiWidgetController calls OpenAI/the AI engine through a raw
    // `new GuzzleClient(...)`, not Laravel's Http:: facade — Http::fake()
    // does not intercept it. Rate limiting itself runs in middleware before
    // the controller, so the request that finally trips 429 costs nothing,
    // but every request *under* the limit still makes a real external call.
    // As written, this test can cost up to ~20 real Claude/AI-engine calls
    // per run. Either mock AiWidgetController's Guzzle client for
    // testability, or run this one manually/occasionally rather than on
    // every CI build.

    public function test_ai_chat_endpoint_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $headers = $this->apiHeaders($user);

        $limit = null;
        $seen = [];
        for ($i = 0; $i < 21; $i++) {
            $response = $this->withHeaders($headers)->postJson('/api/ai/chat', ['message' => 'test message ' . $i]);
            $seen[] = $response->status();
            if ($response->status() === 429) {
                $limit = $i;
                break;
            }
        }

        $this->assertNotNull($limit, 'Expected a 429 within 21 calls — /ai/chat must be rate limited (throttle:20,1). Statuses seen: ' . implode(',', $seen));
    }

    // ── SEC-002: checkout must be rate limited (real stock/order side effects) ──

    public function test_checkout_endpoint_is_rate_limited(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $headers = $this->apiHeaders($user);

        $limit = null;
        $seen = [];
        for ($i = 0; $i < 11; $i++) {
            $response = $this->withHeaders($headers)->postJson('/api/orders/checkout', ['payment_method' => 'wallet']);
            $seen[] = $response->status();
            if ($response->status() === 429) {
                $limit = $i;
                break;
            }
        }

        $this->assertNotNull($limit, 'Expected a 429 within 11 calls — /orders/checkout must be rate limited (throttle:10,1). Statuses seen: ' . implode(',', $seen));
    }

    // ── SEC-006: forged Paystack webhooks must be rejected ──────────────────────

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $response = $this->postJson('/webhooks/paystack', [
            'event' => 'charge.success',
            'data'  => ['reference' => 'FAKE-REF-123', 'status' => 'success'],
        ], [
            'x-paystack-signature' => 'not-a-real-signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_with_missing_signature_is_rejected(): void
    {
        $response = $this->postJson('/webhooks/paystack', [
            'event' => 'charge.success',
            'data'  => ['reference' => 'FAKE-REF-123', 'status' => 'success'],
        ]);

        $response->assertStatus(401);
    }

    // ── 404 message masking fix: record-not-found vs route-not-found ───────────

    public function test_missing_record_returns_resource_not_found_not_endpoint_not_found(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($user))
            ->getJson('/api/farms/999999999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Resource not found.']);
    }

    public function test_genuinely_unmatched_route_returns_endpoint_not_found(): void
    {
        $response = $this->getJson('/api/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Endpoint not found.']);
    }

    // ── Password-reset account-enumeration fix ──────────────────────────────────

    public function test_password_reset_redirect_is_identical_for_existing_and_missing_account(): void
    {
        $user = User::factory()->create(['email' => 'exists-for-test@example.com']);

        $existing = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => 'exists-for-test@example.com',
        ]);
        $missing = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => 'definitely-does-not-exist-xyz@example.com',
        ]);

        $existing->assertRedirect(route('otp.verify'));
        $missing->assertRedirect(route('otp.verify'));
    }

    // ── Audit logging: password reset completion (found + fixed this phase) ────

    public function test_password_reset_completion_is_audited(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'reset_token'   => 'test-reset-token',
            'reset_user_id' => $user->id,
        ])->post('/reset-password', [
            'reset_token'          => 'test-reset-token',
            'password'             => 'NewPassw0rd!23',
            'password_confirmation' => 'NewPassw0rd!23',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'   => 'password.reset_completed',
            'model'    => 'User',
            'model_id' => $user->id,
        ]);
    }
}
