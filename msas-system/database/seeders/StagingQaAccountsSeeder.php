<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 12.2: the four QA accounts for the staging service.
 *
 * - Runs only when APP_ENV=staging; anywhere else it does nothing.
 * - Passwords come from Render secrets that Render generates itself
 *   (generateValue in render.staging.yaml): QA_FARMER_PASSWORD,
 *   QA_DEALER_PASSWORD, QA_EXTENSION_PASSWORD, QA_ADMIN_PASSWORD. They are
 *   never in the repo, never printed, never logged. An account whose secret
 *   is missing or shorter than 16 characters is skipped.
 * - Emails can be overridden with QA_FARMER_EMAIL etc.
 * - Idempotent: docker/start.sh runs it on every staging deploy, so rotating
 *   a secret in Render and redeploying changes the password.
 *
 * Unlike QAAccountsSeeder (which prints credentials), this prints only emails.
 */
class StagingQaAccountsSeeder extends Seeder
{
    public const ACCOUNTS = [
        'FARMER'    => ['role' => 'farmer',            'first' => 'QA', 'last' => 'Farmer',    'email' => 'qa-farmer@staging.msasagro.com'],
        'DEALER'    => ['role' => 'agro-dealer',       'first' => 'QA', 'last' => 'Dealer',    'email' => 'qa-dealer@staging.msasagro.com'],
        'EXTENSION' => ['role' => 'extension-officer', 'first' => 'QA', 'last' => 'Extension', 'email' => 'qa-extension@staging.msasagro.com'],
        'ADMIN'     => ['role' => 'admin',             'first' => 'QA', 'last' => 'Admin',     'email' => 'qa-admin@staging.msasagro.com'],
    ];

    public function run(): void
    {
        if (!app()->environment('staging')) {
            $this->say('warn', 'StagingQaAccountsSeeder runs only when APP_ENV=staging; nothing done.');
            return;
        }

        foreach (self::ACCOUNTS as $key => $spec) {
            $password = (string) (getenv("QA_{$key}_PASSWORD") ?: '');
            $email = strtolower((string) (getenv("QA_{$key}_EMAIL") ?: $spec['email']));
            if (strlen($password) < 16) {
                $this->say('warn', "Skipped {$email}: secret QA_{$key}_PASSWORD is missing or too short.");
                continue;
            }
            $user = User::updateOrCreate(['email' => $email], [
                'first_name' => $spec['first'], 'last_name' => $spec['last'], 'role' => $spec['role'],
                'password' => Hash::make($password), 'is_active' => true, 'is_verified' => true,
                'email_verified_at' => now(), 'state' => 'Katsina', 'country' => 'Nigeria', 'language' => 'en',
            ]);
            $user->forceFill(['is_qa' => true, 'is_test_account' => true, 'force_password_reset' => false])->save();
            $this->say('info', "QA account ready: {$email} ({$spec['role']}). Password: Render secret QA_{$key}_PASSWORD, not shown.");
        }
    }

    private function say(string $level, string $msg): void
    {
        if ($this->command) {
            $this->command->{$level}($msg);
        }
    }
}
