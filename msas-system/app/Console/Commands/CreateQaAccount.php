<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or updates) a dedicated QA test account on staging.
 *
 * The password is read from an environment variable that you fill from the
 * secrets manager; it is never accepted on the command line (shell history)
 * and never printed. Refuses to run in production.
 *
 *   QA_ACCOUNT_PASSWORD=... php artisan qa:create-account qa-farmer@msasagro.test --role=farmer
 */
class CreateQaAccount extends Command
{
    protected $signature = 'qa:create-account {email} {--role=farmer} {--name=QA Tester} {--password-env=QA_ACCOUNT_PASSWORD}';

    protected $description = 'Create a QA test account with the QA scan quota (staging only)';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('QA accounts cannot be created in production.');
            return self::FAILURE;
        }
        if (!config('qa.enabled')) {
            $this->warn('QA_ACCOUNTS_ENABLED is off: the account will be created but its QA quota will not apply.');
        }

        $password = (string) env($this->option('password-env'), '');
        if (strlen($password) < 12) {
            $this->error("Set {$this->option('password-env')} (at least 12 characters) from the secrets manager first.");
            return self::FAILURE;
        }

        [$first, $last] = array_pad(explode(' ', (string) $this->option('name'), 2), 2, 'QA');
        $user = User::updateOrCreate(
            ['email' => strtolower($this->argument('email'))],
            ['first_name' => $first, 'last_name' => $last, 'role' => $this->option('role'), 'password' => Hash::make($password),
             'is_active' => true, 'is_verified' => true, 'email_verified_at' => now()],
        );
        $user->forceFill(['is_qa' => true])->save();

        $this->info("QA account ready: {$user->email} (role {$user->role}, id {$user->id}). Password: from {$this->option('password-env')}, not shown.");
        return self::SUCCESS;
    }
}
