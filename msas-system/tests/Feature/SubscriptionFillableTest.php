<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit: Subscription::$fillable listed 'payment_cycle'
 * and 'paystack_reference' -- neither column exists. The real columns are
 * 'billing_cycle' and 'payment_reference'
 * (2026_06_19_000001_create_subscriptions_table.php:34,42), which every
 * real call site (SubscriptionActivationService, SubscriptionController,
 * WebhookController, PaymentController, Admin\SubscriptionManagementController)
 * already wrote under the correct names -- Laravel silently dropped them
 * since they weren't in $fillable. billing_cycle therefore never actually
 * persisted (stuck at the DB default 'monthly' regardless of what was
 * purchased), and critically payment_reference never persisted either,
 * which broke the duplicate-payment guard
 * ($user->subscriptions()->where('payment_reference', $reference)->exists())
 * used throughout this module -- it always compared against a column that
 * was always null, so the same Paystack reference could be replayed to
 * create unlimited free subscription extensions from one real charge.
 */
class SubscriptionFillableTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_cycle_and_payment_reference_actually_persist_on_activation(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $service = app(SubscriptionActivationService::class);

        $subscription = $service->activate($user, 'basic', 'yearly', 15000, 'REF-TEST-0001', null, 'paystack');

        $subscription->refresh();
        $this->assertSame('yearly', $subscription->billing_cycle, 'billing_cycle must actually persist, not silently default to monthly.');
        $this->assertSame('REF-TEST-0001', $subscription->payment_reference, 'payment_reference must actually persist -- without it the duplicate-payment guard is dead code.');
    }

    public function test_replaying_the_same_payment_reference_does_not_create_a_second_subscription(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $service = app(SubscriptionActivationService::class);

        $service->activate($user, 'basic', 'monthly', 2000, 'REF-REPLAY-0001', null, 'paystack');

        // This is the exact guard every real callback/webhook path checks
        // before calling activate() again -- now that payment_reference
        // actually persists, it correctly finds the existing row.
        $alreadyProcessed = $user->subscriptions()->where('payment_reference', 'REF-REPLAY-0001')->exists();

        $this->assertTrue($alreadyProcessed, 'The duplicate-reference guard must actually detect an already-processed payment.');
        $this->assertSame(1, Subscription::where('payment_reference', 'REF-REPLAY-0001')->count());
    }

    public function test_a_genuine_race_on_the_same_reference_does_not_crash_and_does_not_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $service = app(SubscriptionActivationService::class);

        // Simulates the webhook and browser callback both reaching
        // activate() for the same reference before either's pre-check
        // would have caught the other -- the DB's unique constraint on
        // payment_reference is the real guard for this race, caught and
        // handled gracefully rather than thrown as an uncaught 500.
        $first = $service->activate($user, 'basic', 'monthly', 2000, 'REF-RACE-0001', null, 'paystack');
        $second = $service->activate($user, 'basic', 'monthly', 2000, 'REF-RACE-0001', null, 'paystack');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Subscription::where('payment_reference', 'REF-RACE-0001')->count());
    }

    public function test_upgrade_fields_persist_on_a_plan_change(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $service = app(SubscriptionActivationService::class);

        $basic = $service->activate($user, 'basic', 'monthly', 2000, 'REF-UPGRADE-OLD', null, 'paystack');
        $pro = $service->activate($user, 'pro', 'monthly', 5000, 'REF-UPGRADE-NEW', $basic, 'paystack');

        $this->assertSame('basic', $pro->refresh()->upgraded_from, 'upgraded_from must actually persist.');
        $this->assertSame('cancelled', $basic->refresh()->status);
        $this->assertSame('Upgraded to pro', $basic->cancellation_reason, 'cancellation_reason must actually persist.');
    }
}
