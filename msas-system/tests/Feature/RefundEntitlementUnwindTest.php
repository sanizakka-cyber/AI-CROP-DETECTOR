<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, financially-sensitive cluster:
 * WebhookController::handleRefund() only ever flipped the Payment row's
 * own status to 'refunded' -- it never unwound whatever that payment had
 * activated. A refunded subscriber kept their active plan, a refunded
 * consultation stayed billable, and a refunded order kept counting
 * toward revenue (Order::scopeRevenueCounted() only excludes
 * payment_status values other than 'paid'). This confirms a genuine
 * Paystack refund.processed webhook now actually unwinds each module's
 * entitlement.
 */
class RefundEntitlementUnwindTest extends TestCase
{
    use RefreshDatabase;

    private function postWebhook(string $event, array $data): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode(['event' => $event, 'data' => $data]);
        $signature = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        return $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_x-paystack-signature' => $signature,
        ], $payload);
    }

    public function test_a_refunded_subscription_is_cancelled(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $sub = Subscription::create([
            'user_id' => $user->id, 'plan' => 'premium', 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
            'amount_paid' => 5000, 'payment_reference' => 'SUB-REF-0001',
        ]);
        Payment::create([
            'user_id' => $user->id, 'reference' => 'SUB-REF-0001', 'amount' => 5000,
            'currency' => 'NGN', 'module' => 'subscription', 'status' => 'success',
            'description' => 'Premium subscription',
        ]);

        $this->postWebhook('refund.processed', ['reference' => 'SUB-REF-0001', 'amount' => 5000])
            ->assertOk();

        $sub->refresh();
        $this->assertSame('cancelled', $sub->status, 'A refunded subscription payment must not leave the subscription active.');
        $this->assertSame('refunded', Payment::where('reference', 'SUB-REF-0001')->first()->status);
    }

    public function test_a_refunded_order_no_longer_counts_toward_revenue(): void
    {
        $buyer = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);
        $order = Order::create([
            'order_number' => 'ORD-REFUND-' . uniqid(), 'buyer_id' => $buyer->id, 'dealer_id' => $dealer->id,
            'status' => 'confirmed', 'payment_status' => 'paid', 'subtotal' => 10000, 'total' => 10750,
        ]);
        Payment::create([
            'user_id' => $buyer->id, 'reference' => 'ORD-REF-0001', 'amount' => 10750,
            'currency' => 'NGN', 'module' => 'marketplace', 'module_id' => $order->id, 'status' => 'success',
            'description' => 'Marketplace order',
        ]);

        $this->assertSame(1, Order::revenueCounted()->where('id', $order->id)->count());

        $this->postWebhook('refund.processed', ['reference' => 'ORD-REF-0001', 'amount' => 10750])
            ->assertOk();

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame(0, Order::revenueCounted()->where('id', $order->id)->count(), 'A refunded order must stop counting toward revenue/GMV.');
    }

    public function test_a_refunded_consultation_payment_status_is_updated(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $consultation = Consultation::create([
            'farmer_id' => $farmer->id, 'case_type' => 'livestock', 'status' => 'open',
            'payment_status' => 'paid', 'symptoms' => 'Sick cattle',
        ]);
        Payment::create([
            'user_id' => $farmer->id, 'reference' => 'CONSULT-REF-0001', 'amount' => 2000,
            'currency' => 'NGN', 'module' => 'consultation', 'module_id' => $consultation->id, 'status' => 'success',
            'description' => 'Vet consultation',
        ]);

        $this->postWebhook('refund.processed', ['reference' => 'CONSULT-REF-0001', 'amount' => 2000])
            ->assertOk();

        $this->assertSame('refunded', $consultation->refresh()->payment_status);
    }
}
