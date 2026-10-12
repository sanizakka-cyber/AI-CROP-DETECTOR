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

            // Render's web service disk is ephemeral -- a backup written
            // only to storage_path() is gone on the next deploy or
            // restart. The previous version of this email only reported
            // the file's path/size as a notification; it never actually
            // attached the dump, so the backup never left the container
            // at all. gzip first since a plain-text SQL dump compresses
            // heavily and is more likely to clear typical mail-attachment
            // size limits.
            $gzFile = $file.'.gz';
            exec('gzip -c '.escapeshellarg($file).' > '.escapeshellarg($gzFile), $gzOutput, $gzExit);
            $attachment = ($gzExit === 0 && file_exists($gzFile)) ? $gzFile : $file;
            $attachmentSize = round(filesize($attachment) / 1024, 1);

            // 20MB is comfortably under Gmail's ~25MB attachment ceiling
            // (render.yaml's MAIL_HOST is smtp.gmail.com) while leaving
            // headroom for MIME base64 overhead (~33%).
            $maxAttachmentKb = 20 * 1024;

            try {
                if ($attachmentSize <= $maxAttachmentKb) {
                    Mail::raw(
                        "MSAS FarmAI DB backup completed.\nFile: {$file}\nSize: {$size} KB (attached, compressed: {$attachmentSize} KB)\nTime: ".now()->toDateTimeString(),
                        fn($m) => $m->to($ceoEmail)->subject("DB Backup {$stamp}")->attach($attachment)
                    );
                    $this->info('Backup emailed with attachment.');
                } else {
                    Mail::raw(
                        "MSAS FarmAI DB backup completed but was too large to attach ({$attachmentSize} KB compressed, limit {$maxAttachmentKb} KB).\n".
                        "File: {$file}\nTime: ".now()->toDateTimeString().
                        "\nThis backup has NOT left the server -- retrieve it manually before the next deploy wipes the container's disk.",
                        fn($m) => $m->to($ceoEmail)->subject("DB Backup {$stamp} — TOO LARGE TO ATTACH, RETRIEVE MANUALLY")
                    );
                    $this->warn('Backup too large to email; notified without attachment.');
                    Log::warning('db:backup too large to attach', ['size_kb' => $attachmentSize]);
                }
            } catch (\Throwable $e) {
                $this->warn('Email failed: '.$e->getMessage());
                Log::error('db:backup email failed', ['error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}