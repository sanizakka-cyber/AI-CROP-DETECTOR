<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, marketplace/logistics cluster: the mobile
 * rider API (Api\RiderApiController) drove its own Order::status state
 * machine and let any rider self-assign any pending order. It never
 * touched Order::rider_status, which is what
 * MarketplaceController::confirmDelivery() actually gates its
 * wallet-credit trigger on (rider_status === 'awaiting_confirmation') --
 * so a rider/dealer using the app could never be paid, and riders could
 * grab orders with no admin dispatch step. This confirms the mobile
 * accept -> in-transit -> delivered flow now reaches
 * 'awaiting_confirmation' and that the buyer's web confirmation then
 * actually credits both wallets.
 */
class RiderMobileLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function dispatchedOrder(User $dealer, User $rider, User $buyer): Order
    {
        return Order::create([
            'order_number' => 'ORD-RIDER-' . uniqid(), 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'confirmed', 'payment_status' => 'paid', 'subtotal' => 10000, 'total' => 10750,
            'delivery_fee' => 1000, 'rider_id' => $rider->id, 'rider_status' => 'assigned',
        ]);
    }

    public function test_a_rider_cannot_self_assign_an_unassigned_order(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $buyer  = User::factory()->create(['role' => 'farmer']);
        $rider  = User::factory()->create(['role' => 'rider']);
        $order  = Order::create([
            'order_number' => 'ORD-UNASSIGNED-' . uniqid(), 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'confirmed', 'payment_status' => 'paid', 'subtotal' => 10000, 'total' => 10750,
        ]);

        $response = $this->withHeaders($this->apiHeaders($rider))
            ->postJson("/api/rider/orders/{$order->id}/accept");

        $response->assertStatus(403);
        $this->assertNull($order->refresh()->rider_id, 'A rider must never be able to self-assign an order an admin has not dispatched to them.');
    }

    public function test_the_mobile_accept_to_delivered_flow_reaches_awaiting_confirmation_and_pays_both_wallets(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $buyer  = User::factory()->create(['role' => 'farmer']);
        $rider  = User::factory()->create(['role' => 'rider']);
        $order  = $this->dispatchedOrder($dealer, $rider, $buyer);

        $headers = $this->apiHeaders($rider);

        $this->withHeaders($headers)->postJson("/api/rider/orders/{$order->id}/accept")->assertOk();
        $this->assertSame('accepted', $order->refresh()->rider_status);

        $this->withHeaders($headers)->postJson("/api/rider/orders/{$order->id}/in-transit")->assertOk();
        $this->assertSame('in_transit', $order->refresh()->rider_status);

        $this->withHeaders($headers)->postJson("/api/rider/orders/{$order->id}/delivered")->assertOk();
        $order->refresh();
        $this->assertSame('awaiting_confirmation', $order->rider_status, 'A mobile-delivered order must reach awaiting_confirmation so the buyer can confirm it and trigger wallet payout.');
        $this->assertSame('delivered', $order->status);

        $this->actingAs($buyer)->post(route('marketplace.orders.confirm', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->rider_status);
        $this->assertTrue((bool) $order->rider_credited);
        $this->assertTrue((bool) $order->dealer_credited);

        $riderWallet  = Wallet::where('user_id', $rider->id)->first();
        $dealerWallet = Wallet::where('user_id', $dealer->id)->first();
        $this->assertNotNull($riderWallet);
        $this->assertNotNull($dealerWallet);
        $this->assertEqualsWithDelta(900.0, $riderWallet->balance, 0.01, '90% of the ₦1000 delivery fee must be credited to the rider.');
        $this->assertEqualsWithDelta(9500.0, $dealerWallet->balance, 0.01, '95% of the ₦10000 subtotal must be credited to the dealer.');
    }

    public function test_a_rider_cannot_skip_the_accepted_state_straight_to_in_transit(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $buyer  = User::factory()->create(['role' => 'farmer']);
        $rider  = User::factory()->create(['role' => 'rider']);
        $order  = $this->dispatchedOrder($dealer, $rider, $buyer);

        $response = $this->withHeaders($this->apiHeaders($rider))
            ->postJson("/api/rider/orders/{$order->id}/in-transit");

        $response->assertStatus(422);
        $this->assertSame('assigned', $order->refresh()->rider_status);
    }
}
