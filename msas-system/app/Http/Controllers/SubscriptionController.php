<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionUsage;
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
            $keys = ['livestock_records', 'reports_per_month', 'ai_scans_per_month'];
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
        $request->validate([
            'plan'          => 'required|in:basic,pro,premium',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user      = auth()->user();
        $plan      = $request->plan;
        $cycle     = $request->billing_cycle;
        $activeSub = $user->activeSubscription();

        if ($activeSub && $activeSub->plan === $plan && $activeSub->billing_cycle === $cycle) {
            return back()->with('info', "You already have an active {$plan} plan.");
        }

        // Trial: only once per user
        $hadTrial = $user->subscriptions()
            ->where('plan', $plan)
            ->where('status', 'trial')
            ->exists();

        if (!$hadTrial && $activeSub === null) {
            $user->startTrial($plan);
            return redirect()->route('subscription.dashboard')
                ->with('success', "Your 14-day free trial of the " . config("subscription.plans.{$plan}.name") . " has started!");
        }

        $amount    = config("subscription.plans.{$plan}.price.{$cycle}");
        $reference = 'MSAS-' . strtoupper(Str::random(12));

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

            return redirect($response->json('data.authorization_url'));
        }

        Log::error('Paystack initialization failed', ['response' => $response->json()]);
        return back()->with('error', 'Payment initialization failed. Please try again or contact support.');
    }

    // Paystack callback after payment
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('subscription.plans')->with('error', 'Invalid payment reference.');
        }

        // Verify transaction with Paystack
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

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

    // Paystack webhook (server-to-server)
    public function paystackWebhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $body      = $request->getContent();

        // Verify webhook signature
        if (hash_hmac('sha512', $body, config('services.paystack.secret_key')) !== $signature) {
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $event = $request->json('event');
        $data  = $request->json('data');

        if ($event === 'charge.success') {
            $reference = $data['reference'];
            $meta      = $data['metadata'] ?? [];
            $userId    = $meta['user_id'] ?? null;

            if (!$userId) {
                return response()->json(['status' => 'ok']);
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                return response()->json(['status' => 'ok']);
            }

            $alreadyActivated = $user->subscriptions()
                ->where('payment_reference', $reference)
                ->exists();

            if (!$alreadyActivated) {
                $plan      = $meta['plan'];
                $cycle     = $meta['billing_cycle'];
                $amount    = ($data['amount'] ?? 0) / 100;
                $activeSub = $user->activeSubscription();
                $this->activateSubscription($user, $plan, $cycle, $amount, $reference, $activeSub, 'paystack');
            }
        }

        return response()->json(['status' => 'ok']);
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
        }

        $user->subscriptions()->create([
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
