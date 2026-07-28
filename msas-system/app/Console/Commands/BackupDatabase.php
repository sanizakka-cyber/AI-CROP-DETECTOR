<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup {--email : Email the backup to the CEO}';
    protected $description = 'Dump the PostgreSQL database and store in storage/backups/';

    public function handle(): int
    {
        $dbUrl  = config('database.connections.pgsql');
        $host   = $dbUrl['host'];
        $port   = $dbUrl['port'] ?? 5432;
        $db     = $dbUrl['database'];
        $user   = $dbUrl['username'];
        $pass   = $dbUrl['password'];

        $stamp  = now()->format('Y-m-d_H-i-s');
        $file   = storage_path("backups/msas_backup_{$stamp}.sql");

        @mkdir(storage_path('backups'), 0755, true);

        $env  = "PGPASSWORD={$pass}";
        $cmd  = "{$env} pg_dump -h {$host} -p {$port} -U {$user} -Fp --no-acl --no-owner {$db} > {$file} 2>&1";
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($file)) {
            $this->error('Backup failed. pg_dump exit code: '.$exitCode);
            Log::error('db:backup failed', ['exit' => $exitCode, 'output' => $output]);
            return self::FAILURE;
        }

        $size = round(filesize($file) / 1024, 1);
        $this->info("Backup created: {$file} ({$size} KB)");
        Log::info('db:backup success', ['file' => $file, 'size_kb' => $size]);

        // Keep only last 7 backups
        $backups = glob(storage_path('backups/msas_backup_*.sql'));
        if (count($backups) > 7) {
            usort($backups, fn($a,$b) => filemtime($a) - filemtime($b));
            foreach (array_slice($backups, 0, count($backups) - 7) as $old) {
                @unlink($old);
            }
        }

        if ($this->option('email')) {
            $ceoEmail = config('mail.from.address');
            try {
                Mail::raw(
                    "MSAS FarmAI DB backup completed.\nFile: {$file}\nSize: {$size} KB\nTime: ".now()->toDateTimeString(),
                    fn($m) => $m->to($ceoEmail)->subject("DB Backup {$stamp}")
                );
                $this->info('Backup notification emailed.');
            } catch (\Throwable $e) {
                $this->warn('Email failed: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}