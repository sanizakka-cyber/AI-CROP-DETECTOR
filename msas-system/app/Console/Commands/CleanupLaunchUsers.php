<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupLaunchUsers extends Command
{
    protected $signature   = 'users:cleanup-for-launch
                              {--dry-run : List users that would be deleted without actually deleting them}
                              {--force  : Skip the confirmation prompt}';

    protected $description = 'Delete every user except the CEO account in preparation for public launch.
                              Cascades to all related records (diagnoses, animals, subscriptions, etc.).
                              The CEO account and all its data are preserved.';

    public function handle(): int
    {
        $ceo = User::where('role', 'ceo')->first();

        if (! $ceo) {
            $this->error('No CEO account found. Aborting — nothing was deleted.');
            return self::FAILURE;
        }

        $this->line('');
        $this->info('CEO account that will be PRESERVED:');
        $this->table(['ID', 'Name', 'Email', 'Role'], [
            [$ceo->id, $ceo->name, $ceo->email, $ceo->role],
        ]);

        $targets = User::where('role', '!=', 'ceo')->orderBy('role')->orderBy('id')->get();

        if ($targets->isEmpty()) {
            $this->info('No non-CEO users found. Nothing to delete.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->warn("Users that will be DELETED ({$targets->count()} total):");
        $this->table(
            ['ID', 'Name', 'Email', 'Role'],
            $targets->map(fn ($u) => [$u->id, $u->name ?: '(no name)', $u->email, $u->role])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->line('');
            $this->comment('[dry-run] No changes made.');
            return self::SUCCESS;
        }

        $this->line('');
        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Permanently delete {$targets->count()} user(s) and all their related data?",
                false
            );
            if (! $confirmed) {
                $this->line('Aborted — no changes made.');
                return self::SUCCESS;
            }
        }

        $deleted = 0;
        $errors  = 0;

        DB::transaction(function () use ($targets, &$deleted, &$errors) {
            foreach ($targets as $user) {
                try {
                    $user->delete();
                    $deleted++;
                    $this->line("  Deleted: [{$user->id}] {$user->email} ({$user->role})");
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("  Failed:  [{$user->id}] {$user->email} — {$e->getMessage()}");
                }
            }
        });

        $this->line('');
        $this->info("Done. Deleted {$deleted} user(s)." . ($errors ? " {$errors} error(s) — check output above." : ''));

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
