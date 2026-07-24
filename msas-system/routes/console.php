<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Subscription Lifecycle ─────────────────────────────────────────────────
// Mark subscriptions whose ends_at has passed as expired (runs every hour)
Schedule::command('subscriptions:expire')->hourly();

// Send renewal reminder emails 7 days and 1 day before expiry (runs daily at 07:00 UTC = 08:00 WAT)
Schedule::command('subscriptions:remind')->dailyAt('07:00');
