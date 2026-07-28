<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SystemHealthCheck extends Command
{
    protected $signature   = 'system:health {--alert : Log critical issues}';
    protected $description = 'Run a system health self-check and report status';

    public function handle(): int
    {
        $ok = true;

        // Database
        try {
            DB::select('SELECT 1');
            $this->info('✓ Database: connected');
        } catch (\Throwable $e) {
            $this->error('✗ Database: '.$e->getMessage());
            $ok = false;
        }

        // Failed jobs
        $failed = DB::table('failed_jobs')->count();
        if ($failed > 0) {
            $this->warn("⚠ Failed jobs: {$failed}");
        } else {
            $this->info('✓ Failed jobs: none');
        }

        // Disk space (storage directory)
        $free  = disk_free_space(storage_path());
        $total = disk_total_space(storage_path());
        $pct   = $total > 0 ? round((1 - $free/$total)*100) : 0;
        if ($pct > 85) {
            $this->warn("⚠ Disk usage: {$pct}%");
            $ok = false;
        } else {
            $this->info("✓ Disk usage: {$pct}%");
        }

        // AI engine
        $aiUrl = rtrim(config('services.ai_engine.url', env('AI_ENGINE_URL', '')), '/');
        if ($aiUrl) {
            try {
                $resp = Http::timeout(8)->get($aiUrl.'/health');
                if ($resp->successful()) {
                    $this->info('✓ AI engine: online');
                } else {
                    $this->warn('⚠ AI engine: status '.$resp->status());
                }
            } catch (\Throwable) {
                $this->warn('⚠ AI engine: unreachable');
            }
        }

        // Log file size
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $mb = round(filesize($logPath) / 1048576, 1);
            if ($mb > 50) {
                $this->warn("⚠ laravel.log is {$mb} MB — consider rotating");
            } else {
                $this->info("✓ Log file: {$mb} MB");
            }
        }

        if ($this->option('alert') && !$ok) {
            Log::critical('system:health check found issues', ['failed_jobs' => $failed, 'disk_pct' => $pct]);
        }

        $this->line('');
        $this->info($ok ? 'All checks passed.' : 'One or more checks need attention.');
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}