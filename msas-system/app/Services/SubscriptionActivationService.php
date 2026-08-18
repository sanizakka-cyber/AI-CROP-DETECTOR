<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MobileNotification;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Single source of truth for "initiate Paystack checkout for a subscription"
 * and "verify + activate a subscription after payment" — shared by the web
 * flow (SubscriptionController) and the mobile API flow
 * (Api\SubscriptionApiController), which previously duplicated this logic
 * (and, on the mobile side, never actually implemented it at all).
 */
class SubscriptionActivationService
{
    public function generateReference(): string
    {
        return 'MSAS-' . strtoupper(Str::random(12));
    }

    /**
     * Calls Paystack's /transaction/initialize. $callbackUrl differs between
     * the web flow (a session-backed browser route) and the mobile flow (a
     * public, unauthenticated JSON route) — the metadata carries user_id so
     * activation never has to depend on a live session either way.
     */
    public function initializePaystack(User $user, string $plan, string $cycle, float $amount, string $reference, ?Subscription $activeSub, string $callbackUrl): array
    {
        // Phone-only registered users have email = null (see
        // RegisteredUserController — no email is collected for that signup
        // path). Paystack's /transaction/initialize requires a real email;
        // sending null gets silently rejected and surfaces as a generic
        // "Payment initialization failed" with no clue why. Fall back to
        // notification_email (same fallback User::routeNotificationForMail()
        // already uses), and fail with an actionable message — not a Paystack
        // API call we already know will be rejected — if neither exists.
        $contactEmail = $user->email ?: $user->notification_email;
        if (!$contactEmail) {
            Log::warning('Subscription payment blocked: no email on file', ['user_id' => $user->id]);

            return ['success' => false, 'message' => 'Please add an email address to your profile before subscribing — Paystack requires one to send your payment receipt.'];
        }

        $planName = config("subscription.plans.{$plan}.name");

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . '/transaction/initialize', [
                'email'        => $contactEmail,
                'amount'       => $amount * 100, // kobo
                'reference'    => $reference,
                'currency'     => 'NGN',
                'callback_url' => $callbackUrl,
                'metadata'     => [
                    'module'        => 'subscription',
                    'user_id'       => $user->id,
                    'plan'          => $plan,
                    'billing_cycle' => $cycle,
                    'plan_name'     => $planName,
                    'active_sub_id' => $activeSub?->id,
                ],
            ]);

        if ($response->successful() && $response->json('status')) {
            $authUrl = $response->json('data.authorization_url');
            Log::info('SUBSCRIPTION_AUTHORIZATION_URL_ISSUED', [
                'user_id'   => $user->id,
                'reference' => $reference,
            ]);

            return ['success' => true, 'authorization_url' => $authUrl];
        }

        Log::error('Paystack initialization failed', [
            'user_id'   => $user->id,
            'reference' => $reference,
            'status'    => $response->status(),
            'response'  => $response->json(),
        ]);

        return ['success' => false, 'message' => 'Payment initialization failed. Please try again or contact support.'];
    }

    /**
     * Verifies a reference with Paystack and activates the subscription.
     * $expectedUserId, when given (the web session's authenticated user),
     * is cross-checked against Paystack's own metadata as an anti-tampering
     * guard. When null (the public mobile callback — there is no session to
     * compare against), the user is identified solely from Paystack's
     * verified metadata, which is safe because that response is only
     * reachable by presenting our own secret key.
     */
    public function verifyAndActivate(string $reference, ?int $expectedUserId = null): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        if (!$response->successful() || !$response->json('status') || $response->json('data.status') !== 'success') {
            return ['success' => false, 'message' => 'Payment verification failed. If money was deducted, contact support with reference: ' . $reference];
        }

        $data   = $response->json('data');
        $meta   = $data['metadata'] ?? [];
        $amount = ($data['amount'] ?? 0) / 100;

        $userId = $meta['user_id'] ?? $expectedUserId;
        if (!$userId) {
            return ['success' => false, 'message' => 'Payment metadata missing user reference.'];
        }
        if ($expectedUserId !== null && (int) $userId !== $expectedUserId) {
            Log::warning('Subscription callback: user_id mismatch', [
                'meta_user_id' => $userId,
                'expected'     => $expectedUserId,
                'reference'    => $reference,
            ]);

            return ['success' => false, 'message' => 'Payment reference does not belong to your account. Contact support if you believe this is an error.'];
        }

        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found for this payment.'];
        }

        $plan  = $meta['plan'] ?? null;
        $cycle = $meta['billing_cycle'] ?? null;
        if (!$plan || !$cycle) {
            return ['success' => false, 'message' => 'Payment metadata missing plan details.'];
        }

        // Prevent double-activation — same guard used by the web flow and the
        // generic Payment webhook path.
        if ($user->subscriptions()->where('payment_reference', $reference)->exists()) {
            return ['success' => true, 'duplicate' => true, 'message' => 'This payment has already been processed.'];
        }

        $activeSub = $user->activeSubscription();
        $subscription = $this->activate($user, $plan, $cycle, $amount, $reference, $activeSub, 'paystack');

        return ['success' => true, 'subscription' => $subscription, 'plan' => $plan, 'message' => 'Payment confirmed! You are now on the ' . config("subscription.plans.{$plan}.name") . '.'];
    }

    /** Shared: activate/upgrade the subscription record + payment ledger entry. */
    public function activate(User $user, string $plan, string $cycle, float $amount, string $reference, ?Subscription $activeSub, string $method): Subscription
    {
        $months = $cycle === 'yearly' ? 12 : 1;

        if ($activeSub && $activeSub->plan !== $plan) {
            $activeSub->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => 'Upgraded to ' . $plan,
            ]);
            AuditLog::record('subscription.upgraded_from', 'Subscription', $activeSub->id, [
                'from_plan' => $activeSub->plan,
                'to_plan'   => $plan,
            ]);
        }

        $newSub = $user->subscriptions()->create([
            'plan'              => $plan,
            'status'            => 'active',
            'billing_cycle'     => $cycle,
            'starts_at'         => now(),
            'ends_at'           => now()->addMonths($months),
            'amount_paid'       => $amount,
            'payment_reference' => $reference,
            'payment_method'    => $method,
            'upgraded_from'     => $activeSub?->plan,
            'upgraded_at'       => $activeSub ? now() : null,
        ]);

        AuditLog::record('subscription.activated', 'Subscription', $newSub->id, [
            'plan'          => $plan,
            'billing_cycle' => $cycle,
            'amount'        => $amount,
            'method'        => $method,
        ]);

        Log::info('SUBSCRIPTION_ACTIVATED', [
            'user_id'         => $user->id,
            'subscription_id' => $newSub->id,
            'plan_id'         => $plan,
            'reference'       => $reference,
            'amount'          => $amount,
            'method'          => $method,
        ]);

        if ($method === 'paystack' && $amount > 0 && !Payment::where('reference', $reference)->exists()) {
            $planName = config("subscription.plans.{$plan}.name");
            try {
                Payment::create([
                    'user_id'             => $user->id,
                    'user_type'           => $user->role,
                    'reference'           => $reference,
                    'amount'              => $amount,
                    'currency'            => 'NGN',
                    'status'              => 'success',
                    'payment_method'      => 'paystack',
                    'module'              => 'subscription',
                    'description'         => "{$planName} - " . ucfirst($cycle) . ' subscription',
                    'verification_status' => 'verified',
                    'verified_at'         => now(),
                    'paid_at'             => now(),
                    'receipt_number'      => Payment::generateReceiptNumber(),
                    'metadata'            => ['plan' => $plan, 'billing_cycle' => $cycle],
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Check-then-create isn't atomic: the webhook and the
                // browser/mobile callback can both reach here for the same
                // reference within the same instant. The DB's unique
                // constraint on payments.reference is the real guard — the
                // loser just means the row already exists, not a real error.
                if (!str_contains($e->getMessage(), 'payments_reference_unique') && !str_contains(strtolower($e->getMessage()), 'unique')) {
                    throw $e;
                }
                Log::info('Payment row race: reference already created by a concurrent request', ['reference' => $reference]);
            }
        }

        // Previously the only signal a user got was the immediate page/API
        // response — nothing persistent in-app or push.
        MobileNotification::send(
            $user->id,
            '🎉 Subscription Activated',
            'You are now on the ' . config("subscription.plans.{$plan}.name", $plan) . '. Enjoy full access to your plan features.',
            'subscription',
            ['subscription_id' => $newSub->id, 'plan' => $plan]
        );

        return $newSub;
    }
}
