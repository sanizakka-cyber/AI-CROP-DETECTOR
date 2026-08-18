<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SubscriptionUsage;
use App\Services\SubscriptionActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionActivationService $subs) {}

    // Show plan comparison / selection page
    public function plans()
    {
        $user      = auth()->user();
        $plans     = config('subscription.plans');
        $activeSub = $user->activeSubscription();
        $latestSub = $user->latestSubscription();

        return view('subscription.plans', compact('user', 'plans', 'activeSub', 'latestSub'));
    }

    // Farmer's own subscription dashboard
    public function dashboard()
    {
        $user      = auth()->user();
        $activeSub = $user->activeSubscription();
        $latestSub = $user->latestSubscription();
        $plans     = config('subscription.plans');

        $period = now()->format('Y-m');
        $usage  = [];
        if ($activeSub) {
            $keys = ['ai_scans_per_month', 'livestock_records', 'reports_per_month'];
            // Plans with consultation limits
            if (in_array($activeSub->plan, ['basic_pro', 'premium'])) {
                $keys[] = 'vet_consultations_per_cycle';
                $keys[] = 'agronomist_consultations_per_cycle';
            }
            foreach ($keys as $key) {
                $limit       = $activeSub->getLimit($key);
                $current     = SubscriptionUsage::getCount($user->id, $key, $period);
                $usage[$key] = ['count' => $current, 'limit' => $limit];
            }
        }

        $history = $user->subscriptions()->orderByDesc('created_at')->get();

        return view('subscription.dashboard', compact(
            'user', 'activeSub', 'latestSub', 'plans', 'usage', 'history'
        ));
    }

    // Initiate subscription / upgrade → redirect to Paystack
    public function subscribe(Request $request)
    {
        $validPlans = implode(',', array_keys(config('subscription.plans', [])));
        $request->validate([
            'plan'          => "required|in:{$validPlans}",
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user      = auth()->user();
        $plan      = $request->plan;
        $cycle     = $request->billing_cycle;
        $activeSub = $user->activeSubscription();

        // Only block if the identical plan is already active and PAID (not trial) —
        // a trial never blocks a real purchase, including of the same plan the
        // trial is already for. "Manage Subscription" is one click away via the
        // header link on this page.
        if ($activeSub && $activeSub->plan === $plan && $activeSub->billing_cycle === $cycle && $activeSub->status !== 'trial') {
            return back()->with('info', "You already have an active {$plan} plan.");
        }

        // Clicking Subscribe always initiates payment — it never silently starts a
        // trial instead. The 14-day trial is granted automatically at registration
        // (RegisteredUserController::store()) so it's available from day one
        // without ever standing between a user and a purchase they're ready to
        // make, including on day 1 of that same trial.
        $amount    = config("subscription.plans.{$plan}.price.{$cycle}");
        $reference = $this->subs->generateReference();

        Log::info('SUBSCRIPTION_INIT', [
            'user_id' => $user->id,
            'plan_id' => $plan,
            'cycle'   => $cycle,
            'amount'  => $amount,
            'reference' => $reference,
        ]);

        // If Paystack keys are configured, redirect to payment
        if (config('services.paystack.secret_key') && !str_contains(config('services.paystack.secret_key'), 'REPLACE')) {
            $result = $this->subs->initializePaystack($user, $plan, $cycle, $amount, $reference, $activeSub, route('subscription.paystack.callback'));

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            return redirect($result['authorization_url']);
        }

        // Development fallback: simulate success
        $this->subs->activate($user, $plan, $cycle, $amount, $reference, $activeSub, 'manual');

        return redirect()->route('subscription.dashboard')
            ->with('success', "Successfully subscribed to the " . config("subscription.plans.{$plan}.name") . "!");
    }

    // Paystack callback after payment
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('subscription.plans')->with('error', 'Invalid payment reference.');
        }

        $result = $this->subs->verifyAndActivate($reference, auth()->id());

        if (!$result['success']) {
            return redirect()->route('subscription.plans')->with('error', $result['message']);
        }

        if ($result['duplicate'] ?? false) {
            return redirect()->route('subscription.dashboard')->with('info', $result['message']);
        }

        return redirect()->route('subscription.dashboard')->with('success', $result['message']);
    }

    // Cancel subscription
    public function cancel(Request $request)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $user      = auth()->user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        $activeSub->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'auto_renew'          => false,
            'cancellation_reason' => $request->reason ?? 'User requested cancellation',
        ]);

        AuditLog::record('subscription.cancelled', 'Subscription', $activeSub->id, [
            'plan'   => $activeSub->plan,
            'reason' => $request->reason ?? 'User requested cancellation',
        ]);

        return redirect()->route('subscription.dashboard')
            ->with('success', 'Your subscription has been cancelled. You can still access your plan until ' . $activeSub->ends_at?->format('M d, Y') . '.');
    }

    // Toggle auto-renew
    public function toggleAutoRenew()
    {
        $user      = auth()->user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub) {
            return back()->with('error', 'No active subscription found.');
        }

        $activeSub->update(['auto_renew' => !$activeSub->auto_renew]);
        $state = $activeSub->auto_renew ? 'enabled' : 'disabled';

        return back()->with('success', "Auto-renewal has been {$state}.");
    }
}
