<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| QAAccountsSeeder — FOR TESTING / QA ONLY
|--------------------------------------------------------------------------
| Creates one test account per role so QA can validate every permission
| level without touching production data.
|
| Run:
|   php artisan db:seed --class=QAAccountsSeeder
|
| ── SECURITY RULES ────────────────────────────────────────────────────────
|  1. Passwords are generated randomly at seed time; they are NOT stored
|     anywhere in code.  They are printed ONCE to the terminal so the QA
|     lead can paste them into a password manager immediately.
|  2. Every account is flagged is_test_account = true for easy bulk delete.
|  3. Run the cleanup command BEFORE going to production:
|       php artisan db:seed --class=QACleanupSeeder
|     or:
|       User::where('is_test_account', true)->forceDelete();
|  4. Do NOT commit any file that contains these credentials.
|     storage/qa-credentials-*.txt is already in .gitignore.
|--------------------------------------------------------------------------
*/

class QAAccountsSeeder extends Seeder
{
    private const QA_ACCOUNTS = [
        [
            'role'            => 'farmer',
            'name'            => 'QA Test Farmer',
            'email'           => 'qa-farmer@msas.test',
            'phone'           => '+2348010000001',
            'language'        => 'en',
            'state'           => 'Katsina',
            'lga'             => 'QA District',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'Scan crops & livestock',
                'View own diagnoses',
                'Consult an expert',
                'Browse & order from marketplace',
                'Manage own farm records',
            ],
        ],
        [
            'role'            => 'vet',
            'name'            => 'QA Test Vet',
            'email'           => 'qa-vet@msas.test',
            'phone'           => '+2347010000002',
            'language'        => 'en',
            'state'           => 'Katsina',
            'specialization'  => 'QA Livestock',
            'years_experience'=> 5,
            'consultation_fee'=> 0,
            'license_number'  => 'QA-VET-0001',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'View assigned livestock cases',
                'Write prescriptions',
                'Mark cases complete',
                'View own performance metrics',
                'Verify: CANNOT access crop cases',
            ],
        ],
        [
            'role'            => 'agronomist',
            'name'            => 'QA Test Agronomist',
            'email'           => 'qa-agro@msas.test',
            'phone'           => '+2347010000003',
            'language'        => 'en',
            'state'           => 'Katsina',
            'specialization'  => 'QA Crop Protection',
            'years_experience'=> 4,
            'consultation_fee'=> 0,
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'View assigned crop cases',
                'Write advisory recommendations',
                'Mark cases complete',
                'View own performance metrics',
                'Verify: CANNOT access livestock cases',
            ],
        ],
        [
            'role'            => 'admin',
            'name'            => 'QA Test Admin',
            'email'           => 'qa-admin@msas.test',
            'phone'           => '+2348010000004',
            'language'        => 'en',
            'state'           => 'Katsina',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'User management (list, suspend, edit)',
                'Approve/reject expert applications',
                'View platform analytics',
                'Access admin dashboard',
                'Verify: CANNOT change system settings',
                'Verify: CANNOT access financial controls',
            ],
        ],
        [
            'role'            => 'agro-dealer',
            'name'            => 'QA Test Dealer',
            'email'           => 'qa-dealer@msas.test',
            'phone'           => '+2348010000005',
            'language'        => 'en',
            'state'           => 'Katsina',
            'organization'    => 'QA AgroShop',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'List products for sale',
                'Manage inventory',
                'View and fulfill orders',
                'Request payout',
                'Verify: CANNOT access consultation features',
            ],
        ],
        [
            'role'            => 'extension-officer',
            'name'            => 'QA Test Extension Officer',
            'email'           => 'qa-ext@msas.test',
            'phone'           => '+2348010000006',
            'language'        => 'ha',
            'state'           => 'Katsina',
            'lga'             => 'QA Coverage Area',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'View farms in assigned area',
                'Create diagnoses on behalf of farmers',
                'Escalate cases to experts',
                'Verify: CANNOT approve experts',
                'Verify: CANNOT access financial data',
            ],
        ],
        [
            'role'            => 'ceo',
            'name'            => 'QA Test CEO',
            'email'           => 'qa-ceo@msas.test',
            'phone'           => '+2348010000007',
            'language'        => 'en',
            'state'           => 'Katsina',
            'is_verified'     => true,
            'is_active'       => true,
            'features_to_test'=> [
                'Full system access',
                'Executive dashboard & all analytics',
                'Financial controls and payouts',
                'Change system settings',
                'Emergency controls',
                'Approve/reject any expert or seller',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->warn('');
        $this->command->warn('╔══════════════════════════════════════════════════════════════╗');
        $this->command->warn('║  QA ACCOUNTS SEEDER — CREDENTIALS PRINTED BELOW             ║');
        $this->command->warn('║  Copy them into your password manager NOW.                   ║');
        $this->command->warn('║  These will NOT be shown again.                             ║');
        $this->command->warn('╚══════════════════════════════════════════════════════════════╝');
        $this->command->warn('');

        $summary  = [];
        $logLines = ["MSAS QA Credentials — generated " . now()->toDateTimeString(), str_repeat('-', 72)];

        foreach (self::QA_ACCOUNTS as $spec) {
            $password = $this->generateStrongPassword();

            $userData = array_filter([
                'name'             => $spec['name'],
                'email'            => $spec['email'],
                'phone'            => $spec['phone'],
                'password'         => Hash::make($password),
                'role'             => $spec['role'],
                'language'         => $spec['language'],
                'state'            => $spec['state'],
                'lga'              => $spec['lga']             ?? null,
                'specialization'   => $spec['specialization']  ?? null,
                'years_experience' => $spec['years_experience'] ?? null,
                'consultation_fee' => $spec['consultation_fee'] ?? null,
                'license_number'   => $spec['license_number']  ?? null,
                'organization'     => $spec['organization']    ?? null,
                'is_verified'      => $spec['is_verified'],
                'is_active'        => $spec['is_active'],
                'is_test_account'  => true,
            ], fn($v) => $v !== null);

            User::updateOrCreate(['email' => $spec['email']], $userData);

            $summary[]  = [$spec['role'], $spec['email'], $spec['phone'], $password];
            $logLines[] = "ROLE:     {$spec['role']}";
            $logLines[] = "EMAIL:    {$spec['email']}";
            $logLines[] = "PHONE:    {$spec['phone']}";
            $logLines[] = "PASSWORD: {$password}";
            $logLines[] = "TEST FEATURES:";
            foreach ($spec['features_to_test'] as $f) {
                $logLines[] = "  • {$f}";
            }
            $logLines[] = str_repeat('-', 72);
        }

        // Print credentials table to terminal ONCE.
        $this->command->table(
            ['Role', 'Email', 'Phone', 'Password'],
            $summary
        );

        // Write to storage (excluded from git via default .gitignore on storage/).
        $filename = storage_path('qa-credentials-' . now()->format('Ymd-His') . '.txt');
        file_put_contents($filename, implode("\n", $logLines) . "\n");

        $this->command->warn('');
        $this->command->warn("⚠  Credentials also saved to: {$filename}");
        $this->command->warn('   Move them to your password manager, then delete this file.');
        $this->command->warn('   Run  php artisan msas:qa-cleanup  to delete all test accounts before production.');
        $this->command->warn('');
        $this->command->info('✅ ' . count(self::QA_ACCOUNTS) . ' QA accounts created (is_test_account = true).');
    }

    // ── Generates a 20-char password: mixed case + digits + symbols ───────────
    private function generateStrongPassword(): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '!@#$%^&*';

        $password = '';
        // Guarantee at least 3 of each character class.
        for ($i = 0; $i < 3; $i++) {
            $password .= $upper[random_int(0, strlen($upper) - 1)];
            $password .= $lower[random_int(0, strlen($lower) - 1)];
            $password .= $digits[random_int(0, strlen($digits) - 1)];
            $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        }

        // Pad to 20 characters with random lower-case.
        while (strlen($password) < 20) {
            $pool     = $upper . $lower . $digits . $symbols;
            $password .= $pool[random_int(0, strlen($pool) - 1)];
        }

        // Shuffle so the pattern isn't predictable.
        return str_shuffle($password);
    }
}
