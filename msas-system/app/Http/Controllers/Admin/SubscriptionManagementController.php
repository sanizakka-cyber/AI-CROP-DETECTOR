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
        $plans    = config('subscription.plans', []);
        $planKeys = array_keys($plans);
        $statuses = config('subscription.statuses', []);

        // ── Global stats ───────────────────────────────────────────────────
        $farmerCount   = User::where('role', 'farmer')->count();
        $withSubCount  = Subscription::distinct('user_id')->count('user_id');

        $stats = [
            'total'           => Subscription::count(),
            'active'          => Subscription::where('status', 'active')->count(),
            'trial'           => Subscription::where('status', 'trial')->count(),
            'expired'         => Subscription::where('status', 'expired')->count(),
            'cancelled'       => Subscription::where('status', 'cancelled')->count(),
            'suspended'       => Subscription::where('status', 'suspended')->count(),
            'no_plan'         => max(0, $farmerCount - $withSubCount),
            'revenue'         => Subscription::where('status', 'active')->sum('amount_paid'),
            'revenue_monthly' => Subscription::where('status', 'active')->where('billing_cycle', 'monthly')->sum('amount_paid'),
            'revenue_annual'  => Subscription::where('status', 'active')->where('billing_cycle', 'yearly')->sum('amount_paid'),
        ];

        // ── Per-plan stats ─────────────────────────────────────────────────
        $planStats = [];
        foreach ($planKeys as $pk) {
            $planStats[$pk] = [
                'active'  => Subscription::where('plan', $pk)->where('status', 'active')->count(),
                'trial'   => Subscription::where('plan', $pk)->where('status', 'trial')->count(),
                'expired' => Subscription::where('plan', $pk)->where('status', 'expired')->count(),
                'revenue' => (float) Subscription::where('plan', $pk)->where('status', 'active')->sum('amount_paid'),
            ];
        }

        // ── Query ──────────────────────────────────────────────────────────
        $noPlansView = false;

        if ($request->filled('status') && $request->status === 'no_plan') {
            $noPlansView = true;
            $usersQ = User::where('role', 'farmer')->whereDoesntHave('subscriptions')->latest();
            if ($request->filled('search')) {
                $s = $request->search;
                $usersQ->where(fn($q) =>
                    $q->where('first_name', 'ilike', "%{$s}%")
                      ->orWhere('last_name',  'ilike', "%{$s}%")
                      ->orWhere('email',      'ilike', "%{$s}%")
                      ->orWhere('phone',      'ilike', "%{$s}%")
                );
            }
            $subscriptions = $usersQ->paginate(20)->withQueryString();
        } else {
            $query = Subscription::with(['user', 'activatedBy'])->latest();
            if ($request->filled('plan')) {
                $query->where('plan', $request->plan);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->whereHas('user', fn($q) =>
                    $q->where('first_name', 'ilike', "%{$s}%")
                      ->orWhere('last_name',  'ilike', "%{$s}%")
                      ->orWhere('email',      'ilike', "%{$s}%")
                      ->orWhere('phone',      'ilike', "%{$s}%")
                );
            }
            $subscriptions = $query->paginate(20)->withQueryString();
        }

        return view('admin.subscriptions', compact(
            'subscriptions', 'stats', 'plans', 'statuses', 'planStats', 'noPlansView'
        ));
    }

    // Manually activate or extend a subscription
    public function activate(Request $request, User $user)
    {
        $planKeys = implode(',', array_keys(config('subscription.plans', [])));
        $request->validate([
            'plan'          => "required|in:{$planKeys}",
            'billing_cycle' => 'required|in:monthly,yearly',
            'months'        => 'required|integer|min:1|max:24',
            'notes'         => 'nullable|string|max:500',
        ]);

        $planCfg = config("subscription.plans.{$request->plan}");
        $price   = $planCfg['price'][$request->billing_cycle] ?? 0;

        $user->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $user->subscriptions()->create([
            'plan'              => $request->plan,
            'status'            => 'active',
            'billing_cycle'     => $request->billing_cycle,
            'starts_at'         => now(),
            'ends_at'           => now()->addMonths($request->months),
            'amount_paid'       => $price * $request->months,
            'payment_method'    => 'manual',
            'payment_reference' => 'ADMIN-' . strtoupper(Str::random(10)),
            'notes'             => $request->notes,
            'activated_by'      => auth()->id(),
        ]);

        $planName = $planCfg['name'] ?? ucfirst($request->plan);
        return back()->with('success', "Subscription activated: {$planName} for {$user->name} ({$request->months} month(s)).");
    }

    public function suspend(Subscription $subscription)
    {
        $subscription->update(['status' => 'suspended']);
        return back()->with('success', "Subscription #{$subscription->id} suspended.");
    }

    public function reinstate(Subscription $subscription)
    {
        if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
            $subscription->update(['status' => 'active']);
            return back()->with('success', "Subscription #{$subscription->id} reinstated.");
        }
        return back()->with('error', 'Subscription has already expired. Please create a new one.');
    }

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

    public function grantTrial(Request $request, User $user)
    {
        $planKeys = implode(',', array_keys(config('subscription.plans', [])));
        $request->validate([
            'plan' => "required|in:{$planKeys}",
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

        $planName = config("subscription.plans.{$request->plan}.name", ucfirst($request->plan));
        return back()->with('success', "Free trial ({$request->days} days) granted to {$user->name} for {$planName}.");
    }
}
