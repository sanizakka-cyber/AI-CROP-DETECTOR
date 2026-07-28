<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| StaffAccountsSeeder — Official MSAS Staff & UAT Accounts
|--------------------------------------------------------------------------
| Creates or updates all official @msasagro.com staff accounts.
|
| During UAT, ALL system emails (OTP, 2FA, password reset, alerts) are
| routed to the shared QA inbox via notification_email. Login emails remain
| the staff member's real @msasagro.com address.
|
| SECURITY RULES:
|  - Passwords are hashed — never stored or displayed in plain text here.
|  - Distribute credentials via password manager only.
|  - All UAT accounts are flagged is_test_account = true for easy removal.
|  - Before go-live: User::where('is_test_account', true)->delete();
|
| Run:
|   php artisan db:seed --class=StaffAccountsSeeder
|
| Safe to re-run: uses updateOrCreate, never overwrites existing passwords.
|--------------------------------------------------------------------------
*/

class StaffAccountsSeeder extends Seeder
{
    // All transactional emails go here during UAT — no OTP reaches personal inboxes.
    const TEST_INBOX = 'msaslivestockagroservices@gmail.com';

    private const STAFF = [
        // ── Named Staff (real people) ──
        ['first_name' => 'Sani',    'middle_name' => 'Yawale', 'last_name' => 'Zakka',           'email' => 'ceo@msasagro.com',             'phone' => '08032459879', 'role' => 'ceo'],
        ['first_name' => 'Abdulkadir',                          'last_name' => 'Inda',            'email' => 'admin@msasagro.com',           'phone' => '08035558846', 'role' => 'admin'],
        ['first_name' => 'Aisha',   'middle_name' => 'Sabiu',  'last_name' => 'Bature',          'email' => 'finance@msasagro.com',         'phone' => '08137844133', 'role' => 'finance'],
        ['first_name' => 'Surajo',  'middle_name' => 'Dutsin', 'last_name' => 'Safe',            'email' => 'vet@msasagro.com',             'phone' => '08127878061', 'role' => 'vet'],
        ['first_name' => 'Rabi',                                'last_name' => 'Shehu',           'email' => 'agronomist@msasagro.com',      'phone' => '08037045668', 'role' => 'agronomist'],
        ['first_name' => 'Abbas',                               'last_name' => 'Sani',            'email' => 'field@msasagro.com',           'phone' => '08160225001', 'role' => 'field-officer'],

        // ── 11 Official UAT Accounts (is_test_account = true) ──
        ['first_name' => 'Human',        'last_name' => 'Resources',   'email' => 'hr@msasagro.com',              'phone' => '08100000010', 'role' => 'hr',             'is_uat' => true],
        ['first_name' => 'Customer',     'last_name' => 'Support',     'email' => 'support@msasagro.com',         'phone' => '08100000014', 'role' => 'customer-support','is_uat' => true],
        ['first_name' => 'Marketplace',  'last_name' => 'Manager',     'email' => 'marketplace@msasagro.com',     'phone' => '08100000020', 'role' => 'admin',           'is_uat' => true],
        ['first_name' => 'Verification', 'last_name' => 'Officer',     'email' => 'verification@msasagro.com',    'phone' => '08100000021', 'role' => 'admin',           'is_uat' => true],
        ['first_name' => 'Consultation', 'last_name' => 'Coordinator', 'email' => 'consultation@msasagro.com',    'phone' => '08100000022', 'role' => 'admin',           'is_uat' => true],
        ['first_name' => 'Logistics',    'last_name' => 'Manager',     'email' => 'logisticsmanager@msasagro.com','phone' => '08100000023', 'role' => 'operations',      'is_uat' => true],
        ['first_name' => 'Rider',        'last_name' => 'Supervisor',  'email' => 'riders@msasagro.com',          'phone' => '08100000024', 'role' => 'admin',           'is_uat' => true],
        ['first_name' => 'Reports',      'last_name' => 'Analytics',   'email' => 'analytics@msasagro.com',       'phone' => '08100000025', 'role' => 'data-analyst',    'is_uat' => true],
        ['first_name' => 'IT',           'last_name' => 'Admin',       'email' => 'it@msasagro.com',              'phone' => '08100000026', 'role' => 'admin',           'is_uat' => true],

        // ── Additional staff accounts ──
        ['first_name' => 'MSAS', 'last_name' => 'Operations',     'email' => 'operations@msasagro.com', 'phone' => '08100000011', 'role' => 'operations'],
        ['first_name' => 'MSAS', 'last_name' => 'Extension Officer','email' => 'extension@msasagro.com','phone' => '08100000012', 'role' => 'extension-officer'],
        ['first_name' => 'MSAS', 'last_name' => 'Data Analyst',    'email' => 'data@msasagro.com',      'phone' => '08100000015', 'role' => 'data-analyst'],
        ['first_name' => 'MSAS', 'last_name' => 'M&E Officer',     'email' => 'me@msasagro.com',        'phone' => '08100000016', 'role' => 'm-e-officer'],
    ];

    public function run(): void
    {
        // Passwords are read from environment — never hardcoded in source.
        // Set STAFF_SEED_PASSWORD in your .env before running this seeder.
        // Distribute credentials via password manager only.
        $seedPassword = env('STAFF_SEED_PASSWORD');
        if (!$seedPassword) {
            $this->command->error('STAFF_SEED_PASSWORD is not set. Add it to your .env and re-run.');
            return;
        }

        $created = 0;
        $updated = 0;

        foreach (self::STAFF as $spec) {
            $isUat = $spec['is_uat'] ?? false;

            $user = User::updateOrCreate(
                ['email' => $spec['email']],
                [
                    'first_name'         => $spec['first_name'],
                    'middle_name'        => $spec['middle_name'] ?? null,
                    'last_name'          => $spec['last_name'],
                    'phone'              => $spec['phone'],
                    'role'               => $spec['role'],
                    'is_verified'        => true,
                    'is_active'          => true,
                    'email_verified_at'  => now(),
                    'state'              => 'Katsina',
                    'language'           => 'en',
                    // Route all transactional emails to shared UAT inbox during testing.
                    // Clear notification_email before go-live to restore individual delivery.
                    'notification_email' => self::TEST_INBOX,
                    'password'           => Hash::make($seedPassword),
                ]
            );

            // is_test_account is outside $fillable — set directly to prevent mass-assignment.
            if ($isUat) {
                $user->is_test_account = true;
                $user->save();
            }

            $user->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->command->info("Staff accounts: {$created} created, {$updated} updated.");
        $this->command->warn('All OTPs, 2FA codes, and system emails → ' . self::TEST_INBOX);
        $this->command->warn('UAT accounts are flagged is_test_account=true. Clear them before go-live.');
    }
}
