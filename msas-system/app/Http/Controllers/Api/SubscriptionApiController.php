<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\SubscriptionActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionApiController extends Controller
{
    public function __construct(private SubscriptionActivationService $subs) {}

    public function plans(Request $request): JsonResponse
    {
        $plans     = config('subscription.plans', []);
        $user      = $request->user();
        $activeSub = $user->activeSubscription();
        $planData  = collect($plans)->map(fn($plan, $key) => [
            'key'        => $key,
            'name'       => $plan['name'],
            'price'      => $plan['price'] ?? [],
            'features'   => $plan['features'] ?? [],
            'limits'     => $plan['limits'] ?? [],
            'is_current' => $activeSub && $activeSub->plan === $key,
        ]);
        return response()->json([
            'plans'      => $planData,
            'active_sub' => $activeSub ? [
                'plan'           => $activeSub->plan,
                'status'         => $activeSub->status,
                'ends_at'        => $activeSub->endsAt(),
                'days_remaining' => $activeSub->daysRemaining(),
            ] : null,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $user       = $request->user();
        $validPlans = implode(',', array_keys(config('subscription.plans', [])));
        $data = $request->validate([
            'plan'  => "required|in:{$validPlans}",
            'cycle' => 'required|in:monthly,yearly',
        ]);
        $activeSub = $user->activeSubscription();
        if ($activeSub && $activeSub->plan === $data['plan'] && $activeSub->billing_cycle === $data['cycle'] && $activeSub->status !== 'trial') {
            return response()->json(['error' => 'You are already subscribed to this plan.'], 422);
        }

        $amount    = config("subscription.plans.{$data['plan']}.price.{$data['cycle']}");
        $reference = $this->subs->generateReference();

        if (! config('services.paystack.secret_key') || str_contains(config('services.paystack.secret_key'), 'REPLACE')) {
            return response()->json(['error' => 'Payment service is not configured.'], 503);
        }

        $result = $this->subs->initializePaystack(
            $user, $data['plan'], $data['cycle'], $amount, $reference, $activeSub,
            route('api.subscription.paystack-callback')
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 502);
        }

        return response()->json([
            'message'            => 'Open authorization_url in an in-app browser to complete payment.',
            'reference'          => $reference,
            'amount'             => $amount,
            'plan'               => $data['plan'],
            'cycle'              => $data['cycle'],
            'plan_name'          => config("subscription.plans.{$data['plan']}.name"),
            'authorization_url'  => $result['authorization_url'],
        ]);
    }

    /**
     * Paystack redirects here after the in-app browser completes payment.
     * Public/unauthenticated (mirrors /api/payment/mobile-callback) — the
     * paying user is identified from Paystack's own verified metadata, not
     * from a session, since a webview redirect carries no bearer token.
     */
    public function paystackCallback(Request $request): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return response()->json(['success' => false, 'message' => 'No reference provided.'], 400);
        }

        $result = $this->subs->verifyAndActivate($reference);

        return response()->json([
            'success'   => $result['success'],
            'duplicate' => $result['duplicate'] ?? false,
            'message'   => $result['message'],
            'plan'      => $result['plan'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user      = $request->user();
        $activeSub = $user->activeSubscription();
        if (! $activeSub) {
            return response()->json(['error' => 'No active subscription to cancel.'], 422);
        }
        $activeSub->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'auto_renew'          => false,
            'cancellation_reason' => $request->reason ?? 'User requested cancellation via API',
        ]);
        AuditLog::record('subscription.cancelled', 'Subscription', $activeSub->id, [
            'plan'   => $activeSub->plan,
            'reason' => $request->reason ?? 'API cancellation',
        ]);
        return response()->json([
            'message' => 'Subscription cancelled. Access continues until ' . $activeSub->endsAt()->format('M d, Y') . '.',
            'ends_at' => $activeSub->endsAt(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user      = $request->user();
        $activeSub = $user->activeSubscription();
        if (! $activeSub) {
            return response()->json(['subscribed' => false, 'plan' => null]);
        }
        $limits = collect(config("subscription.plans.{$activeSub->plan}.limits", []))
            ->mapWithKeys(fn($limit, $key) => [$key => [
                'limit'   => $limit,
                'used'    => $activeSub->currentUsage($key),
                'reached' => $activeSub->hasReachedLimit($key),
            ]]);
        return response()->json([
            'subscribed'     => true,
            'plan'           => $activeSub->plan,
            'plan_name'      => $activeSub->planName(),
            'status'         => $activeSub->status,
            'ends_at'        => $activeSub->endsAt(),
            'days_remaining' => $activeSub->daysRemaining(),
            'features'       => config("subscription.plans.{$activeSub->plan}.features", []),
            'limits'         => $limits,
        ]);
    }
}