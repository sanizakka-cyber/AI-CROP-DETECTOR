<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, financially-sensitive cluster: stock
 * availability was checked in PHP before an unconditional
 * Product::decrementStock() with no WHERE guard and no row lock --
 * concurrent checkouts on the last unit(s) of a product could both pass
 * the check and both decrement, driving quantity_in_stock negative
 * (overselling). decrementStock() now guards the UPDATE itself and
 * reports failure; both MarketplaceController::placeOrder() (web) and
 * Api\OrderApiController::checkout() (mobile) now roll back the entire
 * order (not just skip the one item) if stock genuinely ran out between
 * the initial check and the actual decrement.
 */
class MarketplaceStockRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_decrement_stock_fails_without_going_negative_when_insufficient(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $product = Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'Last Bag of Seed',
            'category' => 'seeds', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 1, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);

        $result = $product->decrementStock(2);

        $this->assertFalse($result, 'A decrement larger than available stock must fail, not silently proceed.');
        $this->assertSame(1, $product->refresh()->quantity_in_stock, 'Stock must be untouched after a failed decrement.');
    }

    public function test_decrement_stock_succeeds_and_persists_when_sufficient(): void
    {
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $product = Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'Fertilizer',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);

        $result = $product->decrementStock(3);

        $this->assertTrue($result);
        $this->assertSame(7, $product->refresh()->quantity_in_stock);
    }

    /**
     * A genuine two-request race (the actual scenario decrementStock()'s
     * guard defends against) can't be reproduced in a single synchronous
     * test process -- checkout()'s own pre-check reads the same
     * up-to-date row the later decrement would, so within one request
     * there's no way to make the pre-check pass while the decrement
     * fails without mocking. This instead confirms the ordinary,
     * sufficient-stock checkout path still works correctly end to end
     * with the new atomic decrementStock() in place, and that stock is
     * actually reduced by the right amount -- a regression guard that
     * the fix didn't break normal checkout while hardening the race case.
     */
    public function test_mobile_checkout_still_succeeds_and_decrements_stock_correctly_with_sufficient_supply(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $product = Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'Fertilizer',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);
        \App\Models\CartItem::create(['user_id' => $farmer->id, 'product_id' => $product->id, 'quantity' => 3]);

        $token = $farmer->createToken('test')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/orders/checkout', ['payment_method' => 'paystack']);

        $response->assertStatus(201);
        $this->assertSame(1, \App\Models\Order::count());
        $this->assertSame(7, $product->refresh()->quantity_in_stock);
    }
}
