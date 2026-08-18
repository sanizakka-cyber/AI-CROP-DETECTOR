<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
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
        $reference = 'MSAS-' . strtoupper(Str::random(12));

        Log::info('SUBSCRIPTION_INIT', [
            'user_id' => $user->id,
            'plan_id' => $plan,
            'cycle'   => $cycle,
            'amount'  => $amount,
            'reference' => $reference,
        ]);

        // If Paystack keys are configured, redirect to payment
        if (config('services.paystack.secret_key') && !str_contains(config('services.paystack.secret_key'), 'REPLACE')) {
            return $this->initializePaystackPayment($user, $plan, $cycle, $amount, $reference, $activeSub);
        }

        // Development fallback: simulate success
        return $this->activateSubscription($user, $plan, $cycle, $amount, $reference, $activeSub, 'manual');
    }

    // Paystack payment initialization
    private function initializePaystackPayment($user, $plan, $cycle, $amount, $reference, $activeSub)
    {
        $planName = config("subscription.plans.{$plan}.name");

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . '/transaction/initialize', [
                'email'        => $user->email,
                'amount'       => $amount * 100, // kobo
                'reference'    => $reference,
                'currency'     => 'NGN',
                'callback_url' => route('subscription.paystack.callback'),
                'metadata'     => [
                    'user_id'       => $user->id,
                    'plan'          => $plan,
                    'billing_cycle' => $cycle,
                    'plan_name'     => $planName,
                    'cancel_action' => route('subscription.plans'),
                ],
            ]);

        if ($response->successful() && $response->json('status')) {
            // Store pending subscription intent
            session([
                'pending_sub' => [
                    'plan'          => $plan,
                    'cycle'         => $cycle,
                    'amount'        => $amount,
                    'reference'     => $reference,
                    'active_sub_id' => $activeSub?->id,
                ],
            ]);

            $authUrl = $response->json('data.authorization_url');
            Log::info('SUBSCRIPTION_AUTHORIZATION_URL_ISSUED', [
                'user_id'   => $user->id,
                'reference' => $reference,
                'auth_url'  => $authUrl,
            ]);

            return redirect($authUrl);
        }

        Log::error('Paystack initialization failed', [
            'user_id'   => $user->id,
            'reference' => $reference,
            'status'    => $response->status(),
            'response'  => $response->json(),
        ]);
        return back()->with('error', 'Payment initialization failed. Please try again or contact support.');
    }

    // Paystack callback after payment
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('subscription.plans')->with('error', 'Invalid payment reference.');
        }

        Log::info('SUBSCRIPTION_CALLBACK_RECEIVED', ['reference' => $reference]);

        // Verify transaction with Paystack
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        Log::info('SUBSCRIPTION_VERIFICATION_RESPONSE', [
            'reference' => $reference,
            'status'    => $response->status(),
            'paystack_status' => $response->json('data.status'),
        ]);

        if (!$response->successful() || !$response->json('status') || $response->json('data.status') !== 'success') {
            return redirect()->route('subscription.plans')
                ->with('error', 'Payment verification failed. If money was deducted, contact support with reference: ' . $reference);
        }

        $data      = $response->json('data');
        $meta      = $data['metadata'] ?? [];
        $user      = auth()->user();
        $plan      = $meta['plan'] ?? session('pending_sub.plan');
        $cycle     = $meta['billing_cycle'] ?? session('pending_sub.cycle');
        $amount    = ($data['amount'] ?? 0) / 100;

        // Verify this payment belongs to the authenticated user — prevents
        // an attacker who guesses/intercepts a reference from activating a
        // subscription on their own account at another user's expense.
        if (isset($meta['user_id']) && (int) $meta['user_id'] !== $user->id) {
            Log::warning('Subscription callback: user_id mismatch', [
                'meta_user_id' => $meta['user_id'],
                'auth_id'      => $user->id,
                'reference'    => $reference,
            ]);
            return redirect()->route('subscription.plans')
                ->with('error', 'Payment reference does not belong to your account. Contact support if you believe this is an error.');
        }

        // Prevent double-activation
        $alreadyActivated = $user->subscriptions()
            ->where('payment_reference', $reference)
            ->exists();

        if ($alreadyActivated) {
            return redirect()->route('subscription.dashboard')
                ->with('info', 'This payment has already been processed.');
        }

        $activeSub = $user->activeSubscription();
        $this->activateSubscription($user, $plan, $cycle, $amount, $reference, $activeSub, 'paystack');

        session()->forget('pending_sub');

        return redirect()->route('subscription.dashboard')
            ->with('success', "Payment confirmed! You are now on the " . config("subscription.plans.{$plan}.name") . ".");
    }

    // Shared: activate/upgrade subscription record
    private function activateSubscription($user, $plan, $cycle, $amount, $reference, $activeSub, $method)
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
            'plan'               => $plan,
            'status'             => 'active',
            'billing_cycle'      => $cycle,
            'starts_at'          => now(),
            'ends_at'            => now()->addMonths($months),
            'amount_paid'        => $amount,
            'payment_reference'  => $reference,
            'payment_method'     => $method,
            'upgraded_from'      => $activeSub?->plan,
            'upgraded_at'        => $activeSub ? now() : null,
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

        // Record in unified payments table if a real payment was made
        if ($method === 'paystack' && $amount > 0) {
            $planName = config("subscription.plans.{$plan}.name");
            $exists = Payment::where('reference', $reference)->exists();
            if (!$exists) {
                Payment::create([
                    'user_id'             => $user->id,
                    'user_type'           => $user->role,
                    'reference'           => $reference,
                    'amount'              => $amount,
                    'currency'            => 'NGN',
                    'status'              => 'success',
                    'payment_method'      => 'paystack',
                    'module'              => 'subscription',
                    'description'         => "{$planName} - " . ucfirst($cycle) . " subscription",
                    'verification_status' => 'verified',
                    'verified_at'         => now(),
                    'paid_at'             => now(),
                    'receipt_number'      => Payment::generateReceiptNumber(),
                    'metadata'            => ['plan' => $plan, 'billing_cycle' => $cycle],
                ]);
            }
        }

        return redirect()->route('subscription.dashboard')
            ->with('success', "Successfully subscribed to the " . config("subscription.plans.{$plan}.name") . "!");
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
