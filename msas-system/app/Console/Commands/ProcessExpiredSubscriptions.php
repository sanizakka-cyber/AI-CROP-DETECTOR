<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiredMail;
use App\Models\AuditLog;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire';
    protected $description = 'Mark past-due subscriptions as expired and notify users';

    public function handle(): int
    {
        $expired = Subscription::whereIn('status', ['active', 'trial'])
            ->where('ends_at', '<', now())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expired as $sub) {
            $sub->update(['status' => 'expired']);

            AuditLog::record('subscription.expired', 'Subscription', $sub->id, [
                'plan'       => $sub->plan,
                'ended_at'   => $sub->ends_at?->toISOString(),
                'user_email' => $sub->user?->email,
            ]);

            if ($sub->user?->email) {
                try {
                    Mail::to($sub->user->email)->send(new SubscriptionExpiredMail($sub->user, $sub));
                } catch (\Throwable $e) {
                    Log::warning("[subscriptions:expire] Mail failed for user {$sub->user_id}: {$e->getMessage()}");
                }
            }

            $count++;
        }

        $this->info("Expired {$count} subscription(s).");
        Log::info("[subscriptions:expire] Processed {$count} expired subscriptions.");

        return self::SUCCESS;
    }
}
