<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\Consultation;
use App\Models\User;

class SubscriptionLimitService
{
    public function canScan(User $user): array
    {
        $sub   = $user->subscriptions()->where('status', 'active')->latest()->first();
        $plan  = $sub ? $sub->plan : 'free';
        $limit = $sub ? $sub->getLimit('ai_scans_per_month') : config('subscription.plans.free.limits.ai_scans_per_month', 5);

        if ($limit === -1) {
            return ['allowed' => true, 'used' => 0, 'limit' => -1, 'remaining' => -1];
        }

        $used = Diagnosis::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'allowed'   => $used < $limit,
            'used'      => $used,
            'limit'     => $limit,
            'remaining' => max(0, $limit - $used),
            'plan'      => $plan,
        ];
    }

    public function canConsult(User $user, string $type = 'vet'): array
    {
        $sub   = $user->subscriptions()->where('status', 'active')->latest()->first();
        $key   = $type === 'vet' ? 'vet_consultations_per_cycle' : 'agronomist_consultations_per_cycle';
        $plan  = $sub ? $sub->plan : 'free';
        $limit = $sub ? $sub->getLimit($key) : 0;

        if ($plan === 'free') {
            return [
                'allowed'   => false,
                'used'      => 0,
                'limit'     => 0,
                'remaining' => 0,
                'plan'      => 'free',
                'message'   => 'Consultations require a Basic plan or higher. <a href="' . route('subscription.plans') . '" class="underline font-semibold">Upgrade now →</a>',
            ];
        }

        if ($limit === -1) {
            return ['allowed' => true, 'used' => 0, 'limit' => -1, 'remaining' => -1, 'plan' => $plan];
        }

        $used = Consultation::where('farmer_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $allowed = $used < $limit;

        return [
            'allowed'   => $allowed,
            'used'      => $used,
            'limit'     => $limit,
            'remaining' => max(0, $limit - $used),
            'plan'      => $plan,
            'message'   => $allowed ? null : "You've used all {$limit} consultations for this month. <a href=\"" . route('subscription.plans') . "\" class=\"underline font-semibold\">Upgrade to get more →</a>",
        ];
    }
}
