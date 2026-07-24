<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionReminderMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionReminders extends Command
{
    protected $signature   = 'subscriptions:remind';
    protected $description = 'Send renewal reminder emails 7 days and 1 day before expiry';

    public function handle(): int
    {
        $sent = 0;

        // 7-day reminder — window: 6d 12h → 7d 12h from now
        $seven = Subscription::whereIn('status', ['active', 'trial'])
            ->whereBetween('ends_at', [now()->addDays(6)->addHours(12), now()->addDays(7)->addHours(12)])
            ->whereNull('reminder_7day_sent_at')
            ->with('user')
            ->get();

        foreach ($seven as $sub) {
            $sent += $this->sendReminder($sub, 7);
            $sub->update(['reminder_7day_sent_at' => now()]);
        }

        // 1-day reminder — window: 12h → 36h from now
        $one = Subscription::whereIn('status', ['active', 'trial'])
            ->whereBetween('ends_at', [now()->addHours(12), now()->addHours(36)])
            ->whereNull('reminder_1day_sent_at')
            ->with('user')
            ->get();

        foreach ($one as $sub) {
            $sent += $this->sendReminder($sub, 1);
            $sub->update(['reminder_1day_sent_at' => now()]);
        }

        $this->info("Sent {$sent} reminder email(s).");
        Log::info("[subscriptions:remind] Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    private function sendReminder(Subscription $sub, int $days): int
    {
        if (!$sub->user?->email) return 0;

        try {
            Mail::to($sub->user->email)->send(new SubscriptionReminderMail($sub->user, $sub, $days));
            return 1;
        } catch (\Throwable $e) {
            Log::warning("[subscriptions:remind] Mail failed for user {$sub->user_id}: {$e->getMessage()}");
            return 0;
        }
    }
}
