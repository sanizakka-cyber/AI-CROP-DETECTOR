<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\LoginHistory;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function index()
    {
        $now = now();

        // ── System Health ───────────────────────────────────────────────────
        $dbStatus  = $this->checkDatabase();
        $aiStatus  = $this->checkAiEngine();
        $queueStatus = $this->checkQueue();

        // ── AI Scan Metrics ─────────────────────────────────────────────────
        $scanToday   = Diagnosis::whereDate('created_at', today())->count();
        $scanWeek    = Diagnosis::where('created_at', '>=', now()->subDays(7))->count();
        $scanMonth   = Diagnosis::where('created_at', '>=', now()->subDays(30))->count();
        $scanTotal   = Diagnosis::count();

        $scanSuccessRate = $scanMonth > 0
            ? round(Diagnosis::where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('disease_name')
                ->where('disease_name', '!=', 'Pending Expert Review')
                ->count() / $scanMonth * 100, 1)
            : 0;

        $avgConfidence = round(
            Diagnosis::where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('confidence_score')
                ->avg('confidence_score') ?? 0,
            1
        );

        $scanTrend = Diagnosis::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // ── Payment / Revenue Health ─────────────────────────────────────────
        $paymentsToday   = Payment::whereDate('created_at', today())->count();
        $paySuccessToday = Payment::whereDate('created_at', today())->where('status', 'success')->count();
        $paySuccessRate  = $paymentsToday > 0 ? round($paySuccessToday / $paymentsToday * 100, 1) : 100;

        $revenueToday = Payment::whereDate('created_at', today())
            ->where('status', 'success')->sum('amount');
        $revenueMonth = Payment::where('created_at', '>=', now()->startOfMonth())
            ->where('status', 'success')->sum('amount');
        $revenueTotal = Payment::where('status', 'success')->sum('amount');

        $recentFailedPayments = Payment::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->with('user:id,first_name,last_name')
            ->latest()
            ->take(5)
            ->get();

        // ── User Metrics ────────────────────────────────────────────────────
        $usersTotal     = User::count();
        $usersToday     = User::whereDate('created_at', today())->count();
        $usersWeek      = User::where('created_at', '>=', now()->subDays(7))->count();
        $activeSubCount = Subscription::where('status', 'active')->orWhere('status', 'trial')->count();

        $newUsersTrend = User::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // ── Security Alerts ─────────────────────────────────────────────────
        $recentAudit = AuditLog::with('user:id,first_name,last_name')
            ->whereIn('action', ['login.failed','login.locked','security.breach','password.changed'])
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->take(10)
            ->get();

        $failedLoginsToday = AuditLog::where('action', 'login.failed')
            ->whereDate('created_at', today())
            ->count();

        $lockedAccountsToday = AuditLog::where('action', 'login.locked')
            ->whereDate('created_at', today())
            ->count();

        // ── Queue / Jobs ────────────────────────────────────────────────────
        $failedJobs = DB::table('failed_jobs')->count();
        $pendingJobs = DB::table('jobs')->count();

        // ── Error Log Snapshot ──────────────────────────────────────────────
        $recentErrors = $this->recentErrors();

        return view('ceo.monitoring', compact(
            'dbStatus', 'aiStatus', 'queueStatus',
            'scanToday', 'scanWeek', 'scanMonth', 'scanTotal', 'scanSuccessRate', 'avgConfidence', 'scanTrend',
            'paymentsToday', 'paySuccessToday', 'paySuccessRate',
            'revenueToday', 'revenueMonth', 'revenueTotal', 'recentFailedPayments',
            'usersTotal', 'usersToday', 'usersWeek', 'activeSubCount', 'newUsersTrend',
            'recentAudit', 'failedLoginsToday', 'lockedAccountsToday',
            'failedJobs', 'pendingJobs', 'recentErrors'
        ));
    }

    // ── Refresh single panel via AJAX ───────────────────────────────────────
    public function healthJson()
    {
        return response()->json([
            'db'       => $this->checkDatabase(),
            'ai'       => $this->checkAiEngine(),
            'queue'    => $this->checkQueue(),
            'time'     => now()->toTimeString(),
        ]);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 1);
            return ['ok' => true, 'label' => 'Connected', 'ms' => $ms];
        } catch (\Exception $e) {
            return ['ok' => false, 'label' => 'Error: ' . $e->getMessage(), 'ms' => null];
        }
    }

    private function checkAiEngine(): array
    {
        $url = rtrim(config('services.ai_engine.url', env('AI_ENGINE_URL', '')), '/');
        if (! $url) {
            return ['ok' => false, 'label' => 'Not configured', 'ms' => null];
        }
        try {
            $start    = microtime(true);
            $response = Http::timeout(8)->get($url . '/health');
            $ms       = round((microtime(true) - $start) * 1000);
            $ok       = $response->successful();
            return ['ok' => $ok, 'label' => $ok ? 'Online' : 'Degraded (' . $response->status() . ')', 'ms' => $ms];
        } catch (\Exception $e) {
            return ['ok' => false, 'label' => 'Unreachable', 'ms' => null];
        }
    }

    private function checkQueue(): array
    {
        try {
            $failed  = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();
            $ok = $failed === 0;
            return [
                'ok'      => $ok,
                'label'   => $ok ? 'Healthy' : "{$failed} failed jobs",
                'failed'  => $failed,
                'pending' => $pending,
            ];
        } catch (\Exception $e) {
            return ['ok' => true, 'label' => 'Sync (no queue)', 'failed' => 0, 'pending' => 0];
        }
    }

    private function recentErrors(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (! file_exists($logPath)) return [];

        try {
            $lines  = array_reverse(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
            $errors = [];
            foreach ($lines as $line) {
                if (count($errors) >= 8) break;
                if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL')) {
                    $errors[] = mb_substr(trim($line), 0, 180);
                }
            }
            return $errors;
        } catch (\Exception $e) {
            return [];
        }
    }
}