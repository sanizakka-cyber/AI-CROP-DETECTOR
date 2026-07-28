<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionApiController extends Controller
{
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
        if ($activeSub && $activeSub->plan === $data['plan']) {
            return response()->json(['error' => 'You are already subscribed to this plan.'], 422);
        }
        $amount = config("subscription.plans.{$data['plan']}.price.{$data['cycle']}");
        $ref    = 'MSAS-SUB-' . strtoupper(Str::random(12));
        return response()->json([
            'message'     => 'Proceed to payment to activate your subscription.',
            'payment_ref' => $ref,
            'amount'      => $amount,
            'plan'        => $data['plan'],
            'cycle'       => $data['cycle'],
            'plan_name'   => config("subscription.plans.{$data['plan']}.name"),
            'payment_url' => config('app.url') . '/subscription/pay?ref=' . $ref,
        ]);
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