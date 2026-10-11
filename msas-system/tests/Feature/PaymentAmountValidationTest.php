<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 security audit: Api\PaymentApiController::initiate() previously
 * forwarded $request->amount straight to Paystack with no server-side
 * check at all (unlike the web PaymentController, which already validated
 * amounts against the real price/fee/order total) -- a mobile client
 * could request any subscription/consultation/order for any amount of
 * its own choosing. Both controllers now call the one shared
 * PaymentService::validateAmount(), so this covers both entry points
 * against the same fixture setup.
 */
class PaymentAmountValidationTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_mobile_api_rejects_a_consultation_payment_for_a_different_amount_than_the_real_fee(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $vet    = User::factory()->create(['role' => 'vet']);
        $consultation = Consultation::create([
            'farmer_id' => $farmer->id, 'expert_id' => $vet->id, 'case_type' => 'livestock',
            'symptoms' => 'Test symptoms.', 'status' => 'pending', 'fee' => 5000,
        ]);

        $response = $this->withHeaders($this->apiHeaders($farmer))->postJson('/api/payment/initiate', [
            'module' => 'consultation', 'module_id' => $consultation->id,
            'amount' => 1, // attacker-chosen, far below the real 5000 fee
            'description' => 'Consultation fee',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Payment::count(), 'No payment record should be created for a rejected amount.');
    }

    public function test_mobile_api_rejects_a_consultation_payment_belonging_to_another_farmer(): void
    {
        $owner   = User::factory()->create(['role' => 'farmer']);
        $attacker = User::factory()->create(['role' => 'farmer']);
        $vet = User::factory()->create(['role' => 'vet']);
        $consultation = Consultation::create([
            'farmer_id' => $owner->id, 'expert_id' => $vet->id, 'case_type' => 'livestock',
            'symptoms' => 'Test symptoms.', 'status' => 'pending', 'fee' => 5000,
        ]);

        $response = $this->withHeaders($this->apiHeaders($attacker))->postJson('/api/payment/initiate', [
            'module' => 'consultation', 'module_id' => $consultation->id,
            'amount' => 5000, 'description' => 'Consultation fee',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Payment::count());
    }

    public function test_mobile_api_accepts_a_consultation_payment_for_the_real_fee(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'email' => 'f@example.com']);
        $vet    = User::factory()->create(['role' => 'vet']);
        $consultation = Consultation::create([
            'farmer_id' => $farmer->id, 'expert_id' => $vet->id, 'case_type' => 'livestock',
            'symptoms' => 'Test symptoms.', 'status' => 'pending', 'fee' => 5000,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*/transaction/initialize' => \Illuminate\Support\Facades\Http::response([
                'status' => true,
                'data'   => ['authorization_url' => 'https://paystack.test/x', 'access_code' => 'abc', 'reference' => 'ref'],
            ], 200),
        ]);

        $response = $this->withHeaders($this->apiHeaders($farmer))->postJson('/api/payment/initiate', [
            'module' => 'consultation', 'module_id' => $consultation->id,
            'amount' => 5000, 'description' => 'Consultation fee',
        ]);

        $response->assertOk();
        $this->assertSame(1, Payment::count());
        $this->assertEquals(5000, Payment::first()->amount);
    }

    public function test_mobile_api_ignores_a_tampered_amount_and_charges_the_real_order_total(): void
    {
        $buyer = User::factory()->create(['role' => 'farmer']);
        $order = Order::create(['order_number' => 'ORD-TEST-0001', 'buyer_id' => $buyer->id, 'total' => 15000]);

        \Illuminate\Support\Facades\Http::fake([
            '*/transaction/initialize' => \Illuminate\Support\Facades\Http::response([
                'status' => true,
                'data'   => ['authorization_url' => 'https://paystack.test/x', 'access_code' => 'abc', 'reference' => 'ref'],
            ], 200),
        ]);

        // validateAmount()'s marketplace branch substitutes the order's own
        // total rather than comparing against the client's figure and
        // rejecting -- a different, but equally valid, way of never
        // trusting client input: the attacker-chosen amount is simply
        // discarded, not relied upon at all.
        $response = $this->withHeaders($this->apiHeaders($buyer))->postJson('/api/payment/initiate', [
            'module' => 'marketplace', 'module_id' => $order->id,
            'amount' => 1, 'description' => 'Order payment',
        ]);

        $response->assertOk();
        $this->assertEquals(15000, Payment::first()->amount, 'The real order total must be charged, never the client-supplied figure.');
    }

    public function test_mobile_api_rejects_a_marketplace_payment_for_an_order_that_does_not_exist(): void
    {
        $buyer = User::factory()->create(['role' => 'farmer']);

        $response = $this->withHeaders($this->apiHeaders($buyer))->postJson('/api/payment/initiate', [
            'module' => 'marketplace', 'module_id' => 999999,
            'amount' => 15000, 'description' => 'Order payment',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Payment::count());
    }

    public function test_paystacks_own_reported_amount_must_match_the_recorded_payment_amount_at_verification(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $payment = Payment::create([
            'user_id' => $farmer->id, 'user_type' => 'farmer', 'reference' => 'REF-MISMATCH',
            'amount' => 5000, 'currency' => 'NGN', 'status' => 'pending', 'module' => 'consultation',
            'description' => 'x', 'verification_status' => 'unverified',
        ]);

        $service = app(\App\Services\PaymentService::class);
        // Paystack reports a different amount (in kobo) than what was recorded server-side.
        $result = $service->markSuccessful($payment, ['id' => 1, 'amount' => 100000, 'status' => 'success']);

        $this->assertSame('failed', $result->status, 'A payment amount mismatch at verification must never be marked successful.');
    }
}
