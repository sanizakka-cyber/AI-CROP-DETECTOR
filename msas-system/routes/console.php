<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Subscription Lifecycle ─────────────────────────────────────────────────
Schedule::command('subscriptions:expire')->hourly();
Schedule::command('subscriptions:remind')->dailyAt('07:00');

// ── DevOps ─────────────────────────────────────────────────────────────────
// Daily DB backup at 02:00 UTC (03:00 WAT)
Schedule::command('db:backup --email')->dailyAt('02:00');

// System health check every 6 hours
Schedule::command('system:health --alert')->everySixHours();

// ── CI: Weekly digest email to CEO ─────────────────────────────────────────
Schedule::command('digest:weekly')->weeklyOn(1, '06:00'); // Monday 07:00 WAT
