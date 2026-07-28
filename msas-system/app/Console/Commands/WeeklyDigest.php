<?php

namespace App\Console\Commands;

use App\Models\Diagnosis;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WeeklyDigest extends Command
{
    protected $signature   = 'digest:weekly';
    protected $description = 'Email the CEO a weekly summary of platform activity';

    public function handle(): int
    {
        $ceoEmail = config('mail.ceo_email', env('CEO_EMAIL', config('mail.from.address')));
        $now  = now();
        $week = $now->copy()->subDays(7);

        $newUsers    = User::where('created_at', '>=', $week)->count();
        $totalUsers  = User::count();
        $newScans    = Diagnosis::where('created_at', '>=', $week)->count();
        $totalScans  = Diagnosis::count();
        $revenue     = Payment::where('status','success')->where('created_at', '>=', $week)->sum('amount');
        $revenueMtd  = Payment::where('status','success')->where('created_at', '>=', $now->copy()->startOfMonth())->sum('amount');
        $activeSubs  = Subscription::whereIn('status',['active','trial'])->count();
        $failedJobs  = DB::table('failed_jobs')->count();
        $newFeedback = \App\Models\Feedback::where('created_at', '>=', $week)->count();

        $avgNps = User::whereNotNull('nps_score')->avg('nps_score');

        $body  = "MSAS FarmAI — Weekly Digest\n";
        $body .= str_repeat('=', 40)."\n";
        $body .= "Week ending: ".$now->format('D M d, Y')."\n\n";
        $body .= "USERS\n";
        $body .= "  New this week:  {$newUsers}\n";
        $body .= "  Total:          {$totalUsers}\n";
        $body .= "  Active subs:    {$activeSubs}\n\n";
        $body .= "AI SCANS\n";
        $body .= "  This week:      {$newScans}\n";
        $body .= "  All time:       {$totalScans}\n\n";
        $body .= "REVENUE\n";
        $body .= "  This week:      ₦".number_format($revenue)."\n";
        $body .= "  Month to date:  ₦".number_format($revenueMtd)."\n\n";
        $body .= "OPERATIONS\n";
        $body .= "  Failed jobs:    {$failedJobs}".($failedJobs>0?' ⚠️':'')."\n";
        $body .= "  New feedback:   {$newFeedback}\n";
        if ($avgNps !== null) {
            $body .= "  Avg NPS score:  ".round($avgNps,1)."/10\n";
        }
        $body .= "\n".str_repeat('=', 40)."\n";
        $body .= "MSAS FarmAI Platform · ".config('app.url')."\n";

        try {
            Mail::raw($body, fn($m) => $m
                ->to($ceoEmail)
                ->subject('MSAS Weekly Digest — '.$now->format('M d, Y'))
            );
            $this->info('Weekly digest sent to '.$ceoEmail);
            Log::info('digest:weekly sent', ['to' => $ceoEmail]);
        } catch (\Throwable $e) {
            $this->error('Failed to send digest: '.$e->getMessage());
            Log::error('digest:weekly failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}