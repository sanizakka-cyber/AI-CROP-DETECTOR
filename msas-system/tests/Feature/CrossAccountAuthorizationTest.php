<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8, Section 2: automated authorization tests for permitted and
 * prohibited cross-account actions. These ownership checks were
 * confirmed correct by reading the controller code directly
 * (Api\OrderApiController::show(), Api\ConsultationApiController::
 * show(), Api\ProductApiController::update()/destroy()), but had no
 * regression test locking the behavior in place -- a future edit could
 * silently loosen any of these checks with nothing to catch it.
 */
class CrossAccountAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_a_farmer_cannot_view_another_farmers_order(): void
    {
        $owner  = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $stranger = User::factory()->create(['role' => 'farmer']);
        $order = Order::create([
            'order_number' => 'ORD-IDOR-' . uniqid(), 'buyer_id' => $owner->id, 'dealer_id' => $dealer->id,
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => 5000, 'total' => 5375,
        ]);

        $this->withHeaders($this->apiHeaders($stranger))
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(403);

        $this->withHeaders($this->apiHeaders($owner))
            ->getJson("/api/orders/{$order->id}")
            ->assertOk();

        $this->withHeaders($this->apiHeaders($dealer))
            ->getJson("/api/orders/{$order->id}")
            ->assertOk();
    }

    public function test_a_farmer_cannot_view_another_farmers_consultation(): void
    {
        $owner    = User::factory()->create(['role' => 'farmer']);
        $expert   = User::factory()->create(['role' => 'vet']);
        $stranger = User::factory()->create(['role' => 'farmer']);
        $consultation = Consultation::create([
            'farmer_id' => $owner->id, 'expert_id' => $expert->id, 'case_type' => 'livestock',
            'status' => 'open', 'payment_status' => 'paid', 'symptoms' => 'Lethargic goat',
        ]);

        $this->withHeaders($this->apiHeaders($stranger))
            ->getJson("/api/consultations/{$consultation->id}")
            ->assertStatus(403);

        $this->withHeaders($this->apiHeaders($owner))
            ->getJson("/api/consultations/{$consultation->id}")
            ->assertOk();

        $this->withHeaders($this->apiHeaders($expert))
            ->getJson("/api/consultations/{$consultation->id}")
            ->assertOk();
    }

    public function test_a_dealer_cannot_update_or_delete_another_dealers_product(): void
    {
        $owner    = User::factory()->create(['role' => 'agro-dealer']);
        $stranger = User::factory()->create(['role' => 'agro-dealer']);
        $product = Product::create([
            'dealer_id' => $owner->id, 'seller_type' => 'dealer', 'name' => 'Fertilizer',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'is_approved' => true,
        ]);

        $this->withHeaders($this->apiHeaders($stranger))
            ->putJson("/api/dealer/products/{$product->id}", ['name' => 'Hijacked'])
            ->assertStatus(403);
        $this->assertSame('Fertilizer', $product->refresh()->name);

        $this->withHeaders($this->apiHeaders($stranger))
            ->deleteJson("/api/dealer/products/{$product->id}")
            ->assertStatus(403);
        $this->assertNotNull($product->refresh());

        $this->withHeaders($this->apiHeaders($owner))
            ->putJson("/api/dealer/products/{$product->id}", ['name' => 'Updated Fertilizer'])
            ->assertOk();
        $this->assertSame('Updated Fertilizer', $product->refresh()->name);
    }
}
