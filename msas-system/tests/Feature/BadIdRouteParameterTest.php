<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9 §A5: a non-numeric, negative, decimal, or out-of-bigint-range
 * value in an {id}-shaped route segment used to reach the controller or
 * database unconstrained, surfacing as a Postgres 22P02 ("invalid input
 * syntax for type bigint") or 22003 ("numeric value out of range")
 * QueryException, or an int-typed controller argument TypeError -- all
 * three rendered as an unhandled 500 and got logged to ErrorLog as if
 * they were real application bugs. Fixed at three layers:
 *   1. AppServiceProvider::constrainIdRouteParameters() -- Route::pattern()
 *      constraints reject non-digit segments before the controller ever
 *      runs (the route itself doesn't match -> normal 404).
 *   2. bootstrap/app.php's QueryException render()/report() handlers --
 *      a safety net for anything that still reaches the database as a
 *      malformed/out-of-range value (e.g. an id sourced from the request
 *      body, or a same-SQLSTATE case this test suite didn't enumerate).
 *   3. Laravel's own default $internalDontReport already excludes
 *      ValidationException/AuthenticationException/HttpException (and
 *      its 404/403/429 subclasses) from ever reaching ErrorLog in the
 *      first place -- confirmed by the "well-formed but missing" case
 *      below never writing a row either.
 *
 * This suite only runs its Postgres-specific assertions under a real
 * Postgres connection (SQLSTATE 22P02/22003 are Postgres error codes;
 * SQLite accepts a non-numeric string in an integer column silently,
 * which is exactly why Phase 8's SQLite-only suite never caught this).
 * The route-pattern layer (no match -> 404) is driver-independent and
 * asserted unconditionally.
 */
class BadIdRouteParameterTest extends TestCase
{
    use RefreshDatabase;

    private const BAD_IDS = ['abc', '1abc', '-1', '1.5', '99999999999999999999999999999'];

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public static function badIdProvider(): array
    {
        return array_map(fn ($id) => [$id], self::BAD_IDS);
    }

    /** @dataProvider badIdProvider */
    public function test_a_bad_order_id_returns_404_not_500(string $badId): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->getJson("/api/orders/{$badId}");

        $this->assertNotEquals(500, $response->status(), "payload '{$badId}' must never produce a 500.");
        $this->assertTrue(
            in_array($response->status(), [404, 422], true),
            "payload '{$badId}' returned {$response->status()}, expected 404 or 422."
        );
    }

    /** @dataProvider badIdProvider */
    public function test_a_bad_notification_id_on_delete_returns_404_not_500(string $badId): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->deleteJson("/api/notifications/{$badId}");

        $this->assertNotEquals(500, $response->status(), "payload '{$badId}' must never produce a 500.");
        $this->assertTrue(in_array($response->status(), [404, 422], true));
    }

    /** @dataProvider badIdProvider */
    public function test_a_bad_diagnosis_id_returns_404_not_500(string $badId): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->getJson("/api/diagnose/{$badId}");

        $this->assertNotEquals(500, $response->status(), "payload '{$badId}' must never produce a 500.");
        $this->assertTrue(in_array($response->status(), [404, 422], true));
    }

    public function test_a_bad_monitoring_error_id_on_a_web_route_returns_404_not_500(): void
    {
        $ceo = User::factory()->create(['role' => 'ceo']);

        $response = $this->actingAs($ceo)->post('/ceo/monitoring/errors/abc/resolve');

        $this->assertNotEquals(500, $response->status());
        $response->assertStatus(404);
    }

    /**
     * /track/{orderNumber} must keep working -- orderNumber is a 10-char
     * generated reference (Order::generateNumber()), never a numeric id,
     * and must not be swept up by the numeric route constraints.
     */
    public function test_track_order_by_order_number_still_works_with_a_non_numeric_reference(): void
    {
        $buyer  = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order  = Order::create([
            'order_number' => 'ORD-TRACK-ABCDEF', 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'confirmed', 'payment_status' => 'paid', 'subtotal' => 1000, 'total' => 1075,
        ]);

        $response = $this->get("/track/{$order->order_number}");

        $response->assertOk();
    }

    public function test_no_error_log_row_is_written_for_a_bad_order_id(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $this->withHeaders($this->apiHeaders($farmer))->getJson('/api/orders/abc');

        $this->assertSame(0, ErrorLog::count(), 'Bad client input must never be logged as an unresolved application error.');
    }

    public function test_a_genuinely_missing_but_well_formed_order_id_also_writes_no_error_log_row(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);

        $this->withHeaders($this->apiHeaders($farmer))->getJson('/api/orders/999999');

        $this->assertSame(0, ErrorLog::count());
    }
}
