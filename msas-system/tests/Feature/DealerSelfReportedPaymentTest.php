<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, financially-sensitive cluster: a genuine
 * Paystack-verified marketplace payment is already marked paid
 * automatically by WebhookController::activateOrder() --
 * Api\OrderApiController::markPaid() exists only for offline channels
 * (bank transfer, cash, POS) where gateway verification is impossible.
 * It previously allowed the order's own dealer to self-certify payment
 * with an arbitrary, unverified payment_reference, then self-set
 * status=delivered (updateStatus()), then request a real-money payout
 * (MarketplaceSellController::requestPayout()) on a payment_status/status
 * pair they fully controlled end to end -- a dealer could fabricate a
 * sale and get paid for an order that was never actually bought.
 */
class DealerSelfReportedPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function order(User $dealer): Order
    {
        $buyer = User::factory()->create(['role' => 'farmer']);
        return Order::create([
            'order_number' => 'ORD-TEST-' . uniqid(), 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => 10000, 'total' => 10750,
        ]);
    }

    public function test_a_dealer_cannot_mark_their_own_order_as_paid(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order = $this->order($dealer);

        $response = $this->withHeaders($this->apiHeaders($dealer))
            ->patchJson("/api/dealer/orders/{$order->id}/paid", ['payment_reference' => 'FAKE-REF-0001']);

        $response->assertStatus(403);
        $this->assertSame('unpaid', $order->refresh()->payment_status, 'A dealer must never be able to self-certify their own order as paid.');
    }

    public function test_an_admin_can_confirm_an_offline_payment(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order($dealer);

        $response = $this->withHeaders($this->apiHeaders($admin))
            ->patchJson("/api/dealer/orders/{$order->id}/paid", ['payment_reference' => 'BANK-TRF-0001']);

        $response->assertOk();
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_the_full_fraud_chain_is_broken_at_the_payment_step(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order = $this->order($dealer);

        // Give the dealer an active subscription so the payout route's
        // own 'subscription' middleware doesn't short-circuit the
        // request before it ever reaches requestPayout() -- the chain
        // must be shown broken by the controller's own guards, not by
        // an unrelated middleware redirect.
        Subscription::create([
            'user_id' => $dealer->id, 'plan' => 'basic', 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
            'amount_paid' => 0,
        ]);

        // Attempt the full chain a dishonest dealer would need: self-mark
        // paid, self-mark delivered, then request a payout.
        $this->withHeaders($this->apiHeaders($dealer))
            ->patchJson("/api/dealer/orders/{$order->id}/paid", ['payment_reference' => 'FAKE-REF-0002']);
        $this->withHeaders($this->apiHeaders($dealer))
            ->patchJson("/api/dealer/orders/{$order->id}/status", ['status' => 'delivered']);

        $this->actingAs($dealer)->post(route('marketplace.sell.orders.payout', $order));

        $order->refresh();
        $this->assertSame('unpaid', $order->payment_status, 'Without a genuine paid status, the payout request must be refused.');
        $this->assertNull($order->payout_status);
    }
}
