<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 9 §B1: the cron installed in docker/start.sh runs inside every
// web-container instance. Render's current render.yaml has no
// autoscaling block (single instance today), but onOneServer() costs
// nothing now and prevents every scheduled command here from firing
// once per instance the moment that changes -- duplicate subscription-
// expiry runs, duplicate backup emails, duplicate digest emails, etc.
// Requires an atomic-lock-capable cache driver; render.yaml sets
// CACHE_STORE=database, which Laravel's database cache store supports.
// withoutOverlapping() additionally guards against a single instance's
// own next run starting before a slow previous run (e.g. a backup that
// takes longer than a minute) has finished.

// ── Subscription Lifecycle ─────────────────────────────────────────────────
Schedule::command('subscriptions:expire')->hourly()->onOneServer()->withoutOverlapping();
Schedule::command('subscriptions:remind')->dailyAt('07:00')->onOneServer()->withoutOverlapping();

// ── DevOps ─────────────────────────────────────────────────────────────────
// Daily DB backup at 02:00 UTC (03:00 WAT)
Schedule::command('db:backup --email')->dailyAt('02:00')->onOneServer()->withoutOverlapping();

// System health check every 6 hours
Schedule::command('system:health --alert')->everySixHours()->onOneServer()->withoutOverlapping();

// ── CI: Weekly digest email to CEO ─────────────────────────────────────────
Schedule::command('digest:weekly')->weeklyOn(1, '06:00')->onOneServer()->withoutOverlapping(); // Monday 07:00 WAT
