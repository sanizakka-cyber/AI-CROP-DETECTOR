<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SystemHealthController extends Controller
{
    private const CACHE_TTL = 30; // seconds

    // ── Page (initial load — data rendered server-side) ──────────────────────
    public function index()
    {
        $health = $this->health();
        return view('ceo.health', compact('health'));
    }

    // ── JSON endpoint (polled every 30 s by the dashboard JS) ────────────────
    public function data()
    {
        return response()->json($this->health());
    }

    // ── Aggregate all checks (cached 30 s) ───────────────────────────────────
    private function health(): array
    {
        return Cache::remember('system:health', self::CACHE_TTL, function () {
            return [
                'checked_at' => now()->toIso8601String(),
                'database'   => $this->checkDatabase(),
                'queue'      => $this->checkQueue(),
                'ai'         => $this->checkAiEngine(),
                'paystack'   => $this->checkPaystack(),
                'mail'       => $this->checkMail(),
                'storage'    => $this->checkStorage(),
                'system'     => $this->checkSystem(),
                'ssl'        => $this->checkSsl(),
                'users'      => $this->checkUsers(),
                'errors'     => $this->checkErrors(),
            ];
        });
    }

    // ── Individual checks ─────────────────────────────────────────────────────

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 1);
            return [
                'status'     => $ms < 150 ? 'ok' : ($ms < 600 ? 'warn' : 'error'),
                'latency_ms' => $ms,
                'message'    => "Connected · {$ms} ms",
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'latency_ms' => null, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            $status  = $failed > 0 ? 'warn' : ($pending > 200 ? 'warn' : 'ok');
            return [
                'status'  => $status,
                'pending' => $pending,
                'failed'  => $failed,
                'message' => "{$pending} pending · {$failed} failed",
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'pending' => 0, 'failed' => 0, 'message' => 'Queue table unavailable'];
        }
    }

    private function checkAiEngine(): array
    {
        $url = rtrim((string) env('AI_ENGINE_URL', ''), '/');
        if (!$url) {
            return ['status' => 'warn', 'latency_ms' => null, 'message' => 'AI_ENGINE_URL not configured'];
        }
        try {
            $start    = microtime(true);
            $response = Http::timeout(8)->get($url . '/health');
            $ms       = round((microtime(true) - $start) * 1000, 1);
            $ok       = $response->successful() || $response->status() === 405; // 405 = alive but wrong method
            return [
                'status'     => $ok ? ($ms < 3000 ? 'ok' : 'warn') : 'error',
                'latency_ms' => $ms,
                'message'    => $ok ? "Reachable · {$ms} ms" : 'HTTP ' . $response->status(),
            ];
        } catch (\Throwable) {
            return ['status' => 'error', 'latency_ms' => null, 'message' => 'Unreachable'];
        }
    }

    private function checkPaystack(): array
    {
        try {
            $start    = microtime(true);
            $response = Http::timeout(6)->get('https://api.paystack.co');
            $ms       = round((microtime(true) - $start) * 1000, 1);
            // Paystack returns 200 or 401 on the root — both mean reachable
            $up = $response->status() < 500;
            return [
                'status'     => $up ? ($ms < 1500 ? 'ok' : 'warn') : 'error',
                'latency_ms' => $ms,
                'message'    => $up ? "Reachable · {$ms} ms" : 'HTTP ' . $response->status(),
            ];
        } catch (\Throwable) {
            return ['status' => 'error', 'latency_ms' => null, 'message' => 'Unreachable'];
        }
    }

    private function checkMail(): array
    {
        $mailer = config('mail.default', 'log');
        if ($mailer === 'log') {
            return ['status' => 'warn', 'message' => 'Log driver — not production mail'];
        }
        $host = config("mail.mailers.{$mailer}.host", '');
        if (!$host) {
            return ['status' => 'warn', 'message' => "{$mailer} — host not configured"];
        }
        return ['status' => 'ok', 'message' => strtoupper($mailer) . " · {$host}"];
    }

    private function checkStorage(): array
    {
        try {
            $path  = storage_path();
            $free  = disk_free_space($path);
            $total = disk_total_space($path);
            if ($free === false || $total === false || $total === 0) {
                return ['status' => 'warn', 'used_pct' => null, 'message' => 'Unable to read disk'];
            }
            $usedPct = (int) round((($total - $free) / $total) * 100);
            $freeGb  = round($free / 1_073_741_824, 1);
            $totalGb = round($total / 1_073_741_824, 1);
            return [
                'status'   => $usedPct > 90 ? 'error' : ($usedPct > 75 ? 'warn' : 'ok'),
                'used_pct' => $usedPct,
                'free_gb'  => $freeGb,
                'total_gb' => $totalGb,
                'message'  => "{$usedPct}% used · {$freeGb} GB free of {$totalGb} GB",
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'used_pct' => null, 'message' => 'Disk check failed'];
        }
    }

    private function checkSystem(): array
    {
        try {
            $load     = sys_getloadavg();
            $cores    = (int) (@shell_exec('nproc 2>/dev/null') ?? 1) ?: 1;
            $loadPct  = (int) round(($load[0] / $cores) * 100);

            // System memory via /proc/meminfo (available on Linux/Docker)
            $memUsedPct = null;
            $memTotalGb = null;
            $memFreeGb  = null;
            $memInfo = @file_get_contents('/proc/meminfo');
            if ($memInfo) {
                preg_match('/MemTotal:\s+(\d+)/', $memInfo, $mt);
                preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $ma);
                $memTotalKb = (int) ($mt[1] ?? 0);
                $memAvailKb = (int) ($ma[1] ?? 0);
                if ($memTotalKb > 0) {
                    $memUsedPct = (int) round((($memTotalKb - $memAvailKb) / $memTotalKb) * 100);
                    $memTotalGb = round($memTotalKb / 1_048_576, 1);
                    $memFreeGb  = round($memAvailKb / 1_048_576, 1);
                }
            }

            // Uptime via /proc/uptime
            $uptimeSec = (float) @file_get_contents('/proc/uptime');
            $uptime = $uptimeSec > 0 ? $this->formatUptime($uptimeSec) : 'N/A';

            $status = $loadPct > 90 ? 'error' : ($loadPct > 70 ? 'warn' : ($memUsedPct !== null && $memUsedPct > 90 ? 'error' : ($memUsedPct > 80 ? 'warn' : 'ok')));

            return [
                'status'       => $status,
                'load_1m'      => round($load[0], 2),
                'load_5m'      => round($load[1], 2),
                'load_pct'     => $loadPct,
                'cores'        => $cores,
                'mem_used_pct' => $memUsedPct,
                'mem_total_gb' => $memTotalGb,
                'mem_free_gb'  => $memFreeGb,
                'uptime'       => $uptime,
                'message'      => "Up {$uptime} · CPU {$loadPct}%" . ($memUsedPct !== null ? " · RAM {$memUsedPct}%" : ''),
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'message' => 'System info unavailable'];
        }
    }

    private function formatUptime(float $seconds): string
    {
        $d = (int) ($seconds / 86400);
        $h = (int) (($seconds % 86400) / 3600);
        $m = (int) (($seconds % 3600) / 60);
        if ($d > 0) return "{$d}d {$h}h";
        if ($h > 0) return "{$h}h {$m}m";
        return "{$m}m";
    }

    private function checkSsl(): array
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        if (!$domain || app()->environment('local', 'testing')) {
            return ['status' => 'ok', 'days_left' => null, 'message' => 'Local — no SSL check'];
        }
        try {
            $ctx  = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => true, 'verify_peer_name' => true]]);
            $sock = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if (!$sock) {
                return ['status' => 'error', 'days_left' => null, 'message' => "Cannot connect to {$domain}:443"];
            }
            $params  = stream_context_get_params($sock);
            $cert    = $params['options']['ssl']['peer_certificate'] ?? null;
            fclose($sock);
            if (!$cert) {
                return ['status' => 'warn', 'days_left' => null, 'message' => 'Certificate unreadable'];
            }
            $parsed    = openssl_x509_parse($cert);
            $expiry    = $parsed['validTo_time_t'];
            $daysLeft  = (int) (($expiry - time()) / 86400);
            $expiryFmt = date('d M Y', $expiry);
            return [
                'status'    => $daysLeft < 14 ? 'error' : ($daysLeft < 30 ? 'warn' : 'ok'),
                'days_left' => $daysLeft,
                'expires'   => $expiryFmt,
                'message'   => "{$daysLeft} days remaining · expires {$expiryFmt}",
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'days_left' => null, 'message' => 'SSL check failed'];
        }
    }

    private function checkUsers(): array
    {
        try {
            $cutoff = now()->subMinutes(15)->timestamp;
            $activeIds = DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $cutoff)
                ->pluck('user_id');

            $totalActive  = $activeIds->count();
            $onlineRiders = $activeIds->isNotEmpty()
                ? User::whereIn('id', $activeIds)->where('role', 'rider')->count()
                : 0;

            return [
                'status'        => 'ok',
                'active_15min'  => $totalActive,
                'online_riders' => $onlineRiders,
                'message'       => "{$totalActive} active (15 min) · {$onlineRiders} riders online",
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'active_15min' => 0, 'online_riders' => 0, 'message' => 'Session data unavailable'];
        }
    }

    private function checkErrors(): array
    {
        try {
            $count24h   = ErrorLog::where('created_at', '>=', now()->subDay())->count();
            $unresolved = ErrorLog::where('resolved', false)->count();
            $status     = $count24h > 100 ? 'error' : ($count24h > 20 ? 'warn' : 'ok');
            return [
                'status'     => $status,
                'count_24h'  => $count24h,
                'unresolved' => $unresolved,
                'message'    => "{$count24h} errors (24 h) · {$unresolved} unresolved",
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'count_24h' => 0, 'unresolved' => 0, 'message' => 'Error log unavailable'];
        }
    }
}
