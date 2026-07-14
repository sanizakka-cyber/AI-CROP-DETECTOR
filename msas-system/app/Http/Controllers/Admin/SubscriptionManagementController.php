<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'activatedBy'])->latest();

        // Filters
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) =>
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('phone',      'like', "%{$search}%")
            );
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total'     => Subscription::count(),
            'active'    => Subscription::where('status', 'active')->count(),
            'trial'     => Subscription::where('status', 'trial')->count(),
            'expired'   => Subscription::where('status', 'expired')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'revenue'   => Subscription::where('status', 'active')->sum('amount_paid'),
            'basic'     => Subscription::where('plan', 'basic')->whereIn('status', ['active', 'trial'])->count(),
            'pro'       => Subscription::where('plan', 'pro')->whereIn('status', ['active', 'trial'])->count(),
            'premium'   => Subscription::where('plan', 'premium')->whereIn('status', ['active', 'trial'])->count(),
        ];

        $plans    = config('subscription.plans');
        $statuses = config('subscription.statuses');

        return view('admin.subscriptions', compact('subscriptions', 'stats', 'plans', 'statuses'));
    }

    // Manually activate or extend a subscription for a user
    public function activate(Request $request, User $user)
    {
        $request->validate([
            'plan'          => 'required|in:basic,pro,premium',
            'billing_cycle' => 'required|in:monthly,yearly',
            'months'        => 'required|integer|min:1|max:24',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Cancel any active sub
        $user->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $sub = $user->subscriptions()->create([
            'plan'              => $request->plan,
            'status'            => 'active',
            'billing_cycle'     => $request->billing_cycle,
            'starts_at'         => now(),
            'ends_at'           => now()->addMonths($request->months),
            'amount_paid'       => 0,
            'payment_method'    => 'manual',
            'payment_reference' => 'ADMIN-' . strtoupper(Str::random(10)),
            'notes'             => $request->notes,
            'activated_by'      => auth()->id(),
        ]);

        return back()->with('success', "Subscription activated: {$request->plan} plan for {$user->name} ({$request->months} months).");
    }

    // Suspend a subscription
    public function suspend(Subscription $subscription)
    {
        $subscription->update(['status' => 'suspended']);
        return back()->with('success', "Subscription #{$subscription->id} suspended.");
    }

    // Reinstate a suspended subscription
    public function reinstate(Subscription $subscription)
    {
        if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
            $subscription->update(['status' => 'active']);
            return back()->with('success', "Subscription #{$subscription->id} reinstated.");
        }
        return back()->with('error', 'Subscription has already expired. Please create a new one.');
    }

    // Hard cancel with reason
    public function terminate(Request $request, Subscription $subscription)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $subscription->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->reason,
            'auto_renew'          => false,
        ]);

        return back()->with('success', "Subscription #{$subscription->id} has been terminated.");
    }

    // Grant a free trial manually
    public function grantTrial(Request $request, User $user)
    {
        $request->validate([
            'plan' => 'required|in:basic,pro,premium',
            'days' => 'required|integer|min:1|max:90',
        ]);

        $user->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $user->subscriptions()->create([
            'plan'          => $request->plan,
            'status'        => 'trial',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays($request->days),
            'starts_at'     => now(),
            'ends_at'       => now()->addDays($request->days),
            'amount_paid'   => 0,
            'activated_by'  => auth()->id(),
            'notes'         => "Manual trial granted by admin for {$request->days} days.",
        ]);

        return back()->with('success', "Free trial ({$request->days} days) granted to {$user->name} for {$request->plan} plan.");
    }
}
