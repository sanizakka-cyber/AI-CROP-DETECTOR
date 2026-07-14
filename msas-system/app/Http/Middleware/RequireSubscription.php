<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSubscription
{
    /**
     * Usage: ->middleware('subscription')           - requires any active plan
     *        ->middleware('subscription:pro')       - requires pro or higher
     *        ->middleware('subscription:premium')   - requires premium only
     *        ->middleware('subscription:feature,ai_recommendations') - requires specific feature
     */
    public function handle(Request $request, Closure $next, string $minPlan = 'basic', ?string $feature = null): Response
    {
        $user = $request->user();

        // Non-farmer roles bypass subscription checks
        if ($user && $user->role !== 'farmer') {
            return $next($request);
        }

        $activeSub = $user?->activeSubscription();

        if (!$activeSub) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'subscription_required',
                    'message' => 'An active subscription is required to access this feature.',
                    'plans_url' => route('subscription.plans'),
                ], 402);
            }

            return redirect()->route('subscription.plans')
                ->with('warning', 'You need an active subscription to access this feature. Choose a plan below.');
        }

        // Check minimum plan level
        $requiredLevel = config("subscription.plans.{$minPlan}.plan_level", 1);
        if ($activeSub->planLevel() < $requiredLevel) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'        => 'plan_upgrade_required',
                    'message'      => "This feature requires the " . ucfirst($minPlan) . " plan or higher.",
                    'current_plan' => $activeSub->plan,
                    'required_plan'=> $minPlan,
                ], 402);
            }

            return redirect()->route('subscription.plans')
                ->with('upgrade_required', ucfirst($minPlan))
                ->with('warning', "This feature requires the " . config("subscription.plans.{$minPlan}.name") . " or higher. Upgrade your plan to continue.");
        }

        // Check specific feature access
        if ($feature && !$activeSub->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'feature_not_available',
                    'message' => "This feature is not included in your current plan.",
                ], 402);
            }

            return redirect()->route('subscription.plans')
                ->with('warning', 'This feature is not available on your current plan. Upgrade to unlock it.');
        }

        return $next($request);
    }
}
