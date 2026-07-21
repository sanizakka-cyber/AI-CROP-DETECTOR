<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| TestAccountsSeeder — QA / DEMO ACCOUNTS FOR ALL USER ROLES
|--------------------------------------------------------------------------
| Creates one test account per role so every dashboard and flow can be
| verified end-to-end.
|
| All accounts:
|   - Temporary password:    Welcome@123
|   - force_password_reset:  true  (must change on first login)
|   - is_test_account:       true  (flagged for easy cleanup)
|   - No real personal data / no real payment details
|
| Run:
|   php artisan db:seed --class=TestAccountsSeeder
|
| IMPORTANT — BEFORE GO-LIVE:
|   Delete or disable all test accounts:
|   User::where('is_test_account', true)->delete();
|
| Credentials must be stored in a password manager only.
| NEVER commit credentials to git or share via email/Slack.
|--------------------------------------------------------------------------
*/

class TestAccountsSeeder extends Seeder
{
    private const TEMP_PASSWORD = 'Welcome@123';

    private const ACCOUNTS = [
        [
            'first_name' => 'Test',
            'last_name'  => 'CEO',
            'email'      => 'test.ceo@qa.msasagro.local',
            'phone'      => '07000000001',
            'role'       => 'ceo',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Admin',
            'email'      => 'test.admin@qa.msasagro.local',
            'phone'      => '07000000002',
            'role'       => 'admin',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Farmer',
            'email'      => 'test.farmer@qa.msasagro.local',
            'phone'      => '07000000003',
            'role'       => 'farmer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Veterinarian',
            'email'      => 'test.vet@qa.msasagro.local',
            'phone'      => '07000000004',
            'role'       => 'vet',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Agronomist',
            'email'      => 'test.agronomist@qa.msasagro.local',
            'phone'      => '07000000005',
            'role'       => 'agronomist',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'AgroDealer',
            'email'      => 'test.agrodealer@qa.msasagro.local',
            'phone'      => '07000000006',
            'role'       => 'agro-dealer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'EquipDealer',
            'email'      => 'test.equipdealer@qa.msasagro.local',
            'phone'      => '07000000007',
            'role'       => 'equipment-dealer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'AgribizOwner',
            'email'      => 'test.agribiz@qa.msasagro.local',
            'phone'      => '07000000008',
            'role'       => 'agribusiness-owner',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Cooperative',
            'email'      => 'test.cooperative@qa.msasagro.local',
            'phone'      => '07000000009',
            'role'       => 'cooperative',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'GovAgency',
            'email'      => 'test.gov@qa.msasagro.local',
            'phone'      => '07000000010',
            'role'       => 'government-agency',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'NGO',
            'email'      => 'test.ngo@qa.msasagro.local',
            'phone'      => '07000000011',
            'role'       => 'ngo',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Researcher',
            'email'      => 'test.research@qa.msasagro.local',
            'phone'      => '07000000012',
            'role'       => 'research-institution',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'InputSupplier',
            'email'      => 'test.supplier@qa.msasagro.local',
            'phone'      => '07000000013',
            'role'       => 'input-supplier',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Logistics',
            'email'      => 'test.logistics@qa.msasagro.local',
            'phone'      => '07000000014',
            'role'       => 'logistics-provider',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Investor',
            'email'      => 'test.investor@qa.msasagro.local',
            'phone'      => '07000000015',
            'role'       => 'investor',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'GeneralUser',
            'email'      => 'test.general@qa.msasagro.local',
            'phone'      => '07000000016',
            'role'       => 'general-user',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Extension',
            'email'      => 'test.extension@qa.msasagro.local',
            'phone'      => '07000000017',
            'role'       => 'extension-officer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'FieldOfficer',
            'email'      => 'test.field@qa.msasagro.local',
            'phone'      => '07000000018',
            'role'       => 'field-officer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'DataAnalyst',
            'email'      => 'test.data@qa.msasagro.local',
            'phone'      => '07000000019',
            'role'       => 'data-analyst',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'MEOfficer',
            'email'      => 'test.me@qa.msasagro.local',
            'phone'      => '07000000020',
            'role'       => 'm-e-officer',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Support',
            'email'      => 'test.support@qa.msasagro.local',
            'phone'      => '07000000021',
            'role'       => 'customer-support',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'HR',
            'email'      => 'test.hr@qa.msasagro.local',
            'phone'      => '07000000022',
            'role'       => 'hr',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Finance',
            'email'      => 'test.finance@qa.msasagro.local',
            'phone'      => '07000000023',
            'role'       => 'finance',
        ],
        [
            'first_name' => 'Test',
            'last_name'  => 'Operations',
            'email'      => 'test.operations@qa.msasagro.local',
            'phone'      => '07000000024',
            'role'       => 'operations',
        ],
    ];

    public function run(): void
    {
        $created = 0;
        $skipped = 0;

        foreach (self::ACCOUNTS as $spec) {
            if (User::where('email', $spec['email'])->exists()) {
                $skipped++;
                continue;
            }

            User::create([
                'first_name'           => $spec['first_name'],
                'last_name'            => $spec['last_name'],
                'email'                => $spec['email'],
                'phone'                => $spec['phone'],
                'password'             => Hash::make(self::TEMP_PASSWORD),
                'role'                 => $spec['role'],
                'is_verified'          => true,
                'is_active'            => true,
                'force_password_reset' => true,
                'is_test_account'      => true,
                'state'                => 'Katsina',
                'language'             => 'en',
            ]);
            $created++;
        }

        $this->command->info("Test accounts: {$created} created, {$skipped} already existed (skipped).");
        $this->command->warn('Temporary password for all new test accounts: ' . self::TEMP_PASSWORD);
        $this->command->warn('All accounts flagged is_test_account = true. Run User::where("is_test_account", true)->delete() before going live.');
    }
}
