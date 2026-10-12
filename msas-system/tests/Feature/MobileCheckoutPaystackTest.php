<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 10 (Android/mobile parity audit): the web buyer-facing checkout
 * (MarketplaceController::placeOrder()) has always initialized a single
 * combined Paystack transaction for the grand total and returned its
 * authorization_url -- Api\OrderApiController::checkout() (the mobile
 * flow) never did either. Selecting "Paystack" (the default and only
 * fully-functional option in the mobile cart UI) created an order the
 * buyer had no way to actually pay for through the app at all. Fixed by
 * mirroring the web flow's initialization exactly, plus a dedicated,
 * stateless callback (mobilePaymentCallback()) since the web callback
 * reads an authenticated session an in-app browser never has.
 */
class MobileCheckoutPaystackTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function cartFor(User $farmer): Product
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $product = Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'Fertilizer',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);
        CartItem::create(['user_id' => $farmer->id, 'product_id' => $product->id, 'quantity' => 2]);
        return $product;
    }

    public function test_checkout_with_paystack_returns_a_real_authorization_url(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'email' => 'farmer@example.com']);
        $this->cartFor($farmer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data'   => ['authorization_url' => 'https://paystack.test/pay/abc123', 'reference' => 'MOCKED-REF'],
            ], 200),
        ]);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->postJson('/api/orders/checkout', ['payment_method' => 'paystack']);

        $response->assertStatus(201);
        $response->assertJsonPath('payment.authorization_url', 'https://paystack.test/pay/abc123');

        $order = Order::where('buyer_id', $farmer->id)->first();
        $this->assertNotNull($order->payment_reference, 'The created order must be tagged with the Paystack reference so the callback can find it later.');
        $this->assertSame('unpaid', $order->payment_status, 'Checkout only starts the transaction -- it must not mark the order paid before the callback verifies it.');
    }

    public function test_checkout_falls_back_gracefully_when_paystack_init_fails(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $this->cartFor($farmer);

        Http::fake(['*/transaction/initialize' => Http::response(['status' => false], 500)]);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->postJson('/api/orders/checkout', ['payment_method' => 'paystack']);

        // The order itself must still be created -- a Paystack outage must
        // never block checkout entirely, only the payment step.
        $response->assertStatus(201);
        $response->assertJsonPath('payment.authorization_url', null);
        $this->assertSame(1, Order::where('buyer_id', $farmer->id)->count());
    }

    public function test_mobile_marketplace_callback_marks_the_order_paid_on_verified_success(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order = Order::create([
            'order_number' => 'ORD-CB-' . uniqid(), 'buyer_id' => $farmer->id, 'dealer_id' => $dealer->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => 3000, 'total' => 3225,
            'payment_reference' => 'REAL-REF-0001',
        ]);

        Http::fake([
            '*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']], 200),
        ]);

        $response = $this->getJson('/api/payment/mobile-marketplace-callback?reference=REAL-REF-0001');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_mobile_marketplace_callback_does_not_mark_paid_on_failed_verification(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order = Order::create([
            'order_number' => 'ORD-CB-' . uniqid(), 'buyer_id' => $farmer->id, 'dealer_id' => $dealer->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => 3000, 'total' => 3225,
            'payment_reference' => 'FAKE-REF-0002',
        ]);

        Http::fake([
            '*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'failed']], 200),
        ]);

        $response = $this->getJson('/api/payment/mobile-marketplace-callback?reference=FAKE-REF-0002');

        $response->assertStatus(422);
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_mobile_marketplace_callback_requires_no_authenticated_session(): void
    {
        // The entire point of this endpoint: an in-app browser calling it
        // has no web session. Deliberately does NOT use actingAs()/
        // withHeaders() -- a genuinely anonymous request.
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $buyer  = User::factory()->create(['role' => 'farmer']);
        $order = Order::create([
            'order_number' => 'ORD-CB-' . uniqid(), 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => 1000, 'total' => 1075,
            'payment_reference' => 'ANON-REF-0003',
        ]);

        Http::fake([
            '*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']], 200),
        ]);

        $response = $this->getJson('/api/payment/mobile-marketplace-callback?reference=ANON-REF-0003');

        $response->assertOk();
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_checkout_rejects_the_removed_flutterwave_payment_method(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $this->cartFor($farmer);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->postJson('/api/orders/checkout', ['payment_method' => 'flutterwave']);

        $response->assertStatus(422);
        $this->assertSame(0, Order::count(), 'No Flutterwave integration exists anywhere in this codebase -- an order must never be created for it.');
    }

    public function test_a_multi_dealer_cart_shares_one_payment_reference_across_both_orders(): void
    {
        $farmer  = User::factory()->create(['role' => 'farmer']);
        $dealerA = User::factory()->create(['role' => 'agro-dealer']);
        $dealerB = User::factory()->create(['role' => 'agro-dealer']);
        $productA = Product::create([
            'dealer_id' => $dealerA->id, 'seller_type' => 'dealer', 'name' => 'Seed',
            'category' => 'seeds', 'unit' => 'bag', 'cost_price' => 500, 'selling_price' => 800,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);
        $productB = Product::create([
            'dealer_id' => $dealerB->id, 'seller_type' => 'dealer', 'name' => 'Fertilizer',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);
        CartItem::create(['user_id' => $farmer->id, 'product_id' => $productA->id, 'quantity' => 1]);
        CartItem::create(['user_id' => $farmer->id, 'product_id' => $productB->id, 'quantity' => 1]);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data'   => ['authorization_url' => 'https://paystack.test/pay/multi', 'reference' => 'MULTI-REF'],
            ], 200),
        ]);

        $response = $this->withHeaders($this->apiHeaders($farmer))
            ->postJson('/api/orders/checkout', ['payment_method' => 'paystack']);

        $response->assertStatus(201);
        $this->assertSame(2, Order::where('buyer_id', $farmer->id)->count(), 'One order per dealer.');
        $this->assertSame(
            1,
            Order::where('buyer_id', $farmer->id)->distinct()->count('payment_reference'),
            'Both dealers\' orders must share the exact same reference so one combined payment covers both.'
        );
    }
}
