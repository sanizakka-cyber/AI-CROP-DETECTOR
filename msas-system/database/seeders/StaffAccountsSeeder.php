<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| StaffAccountsSeeder — PRODUCTION STAFF ACCOUNTS
|--------------------------------------------------------------------------
| Creates official @msasagro.com accounts for every staff role.
| Every NEW account has force_password_reset = true so the user must change
| their password on first login.
|
| Re-running this seeder is SAFE — it will NOT reset passwords or the
| force_password_reset flag for accounts that already exist. Only profile
| fields (name, role, department) are updated on existing accounts.
|
| Run:
|   php artisan db:seed --class=StaffAccountsSeeder
|
| After running, distribute credentials via a password manager only.
| Do NOT email or share passwords in plain text.
|--------------------------------------------------------------------------
*/

class StaffAccountsSeeder extends Seeder
{
    private const STAFF = [
        [
            'first_name'  => 'Sani',
            'middle_name' => 'Yawale',
            'last_name'   => 'Zakka',
            'email'       => 'ceo@msasagro.com',
            'phone'       => '08032459879',
            'role'        => 'ceo',
            'department'  => 'Executive',
        ],
        [
            'first_name'  => 'Abdulkadir',
            'last_name'   => 'Inda',
            'email'       => 'admin@msasagro.com',
            'phone'       => '08035558846',
            'role'        => 'admin',
            'department'  => 'Administration',
        ],
        [
            'first_name'  => 'Aisha',
            'middle_name' => 'Sabiu',
            'last_name'   => 'Bature',
            'email'       => 'finance@msasagro.com',
            'phone'       => '08137844133',
            'role'        => 'finance',
            'department'  => 'Finance',
        ],
        [
            'first_name'  => 'Surajo',
            'middle_name' => 'Dutsin',
            'last_name'   => 'Safe',
            'email'       => 'vet@msasagro.com',
            'phone'       => '08127878061',
            'role'        => 'vet',
            'department'  => 'Veterinary Services',
            'specialization' => 'Livestock Health',
        ],
        [
            'first_name'  => 'Rabi',
            'last_name'   => 'Shehu',
            'email'       => 'agronomist@msasagro.com',
            'phone'       => '08037045668',
            'role'        => 'agronomist',
            'department'  => 'Agronomy',
            'specialization' => 'Crop Protection',
        ],
        [
            'first_name'  => 'Abbas',
            'last_name'   => 'Sani',
            'email'       => 'field@msasagro.com',
            'phone'       => '08160225001',
            'role'        => 'field-officer',
            'department'  => 'Field Operations',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'HR',
            'email'       => 'hr@msasagro.com',
            'phone'       => '08100000010',
            'role'        => 'hr',
            'department'  => 'Human Resources',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Operations',
            'email'       => 'operations@msasagro.com',
            'phone'       => '08100000011',
            'role'        => 'operations',
            'department'  => 'Operations',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Extension Officer',
            'email'       => 'extension@msasagro.com',
            'phone'       => '08100000012',
            'role'        => 'extension-officer',
            'department'  => 'Extension Services',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Dealer',
            'email'       => 'dealer@msasagro.com',
            'phone'       => '08100000013',
            'role'        => 'agro-dealer',
            'department'  => 'Agro-Input Supply',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Support',
            'email'       => 'support@msasagro.com',
            'phone'       => '08100000014',
            'role'        => 'customer-support',
            'department'  => 'Customer Support',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Data Analyst',
            'email'       => 'data@msasagro.com',
            'phone'       => '08100000015',
            'role'        => 'data-analyst',
            'department'  => 'Data & Analytics',
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'M&E Officer',
            'email'       => 'me@msasagro.com',
            'phone'       => '08100000016',
            'role'        => 'm-e-officer',
            'department'  => 'Monitoring & Evaluation',
        ],
    ];

    // Default initial password for new staff accounts (must be changed on first login).
    // Distribute via password manager only — never in email or plain text.
    private const INITIAL_PASSWORD = 'Welcome@123';

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        foreach (self::STAFF as $spec) {
            $existing = User::where('email', $spec['email'])->first();

            // Fields that are always safe to update (profile data only)
            $profileData = array_filter([
                'first_name'     => $spec['first_name'],
                'middle_name'    => $spec['middle_name'] ?? null,
                'last_name'      => $spec['last_name'],
                'phone'          => $spec['phone'],
                'role'           => $spec['role'],
                'department'     => $spec['department'] ?? null,
                'specialization' => $spec['specialization'] ?? null,
                'is_verified'    => true,
                'is_active'      => true,
                'state'          => 'Katsina',
                'language'       => 'en',
            ], fn($v) => $v !== null);

            if ($existing) {
                // NEVER overwrite password or force_password_reset for existing accounts.
                // Staff may have already changed their credentials — resetting would lock them out.
                $existing->update($profileData);
                $updated++;
            } else {
                User::create(array_merge($profileData, [
                    'email'                => $spec['email'],
                    'password'             => Hash::make(self::INITIAL_PASSWORD),
                    'force_password_reset' => true,
                    'is_test_account'      => false,
                ]));
                $created++;
            }
        }

        $this->command->info("Staff accounts: {$created} created, {$updated} updated.");
        if ($created > 0) {
            $this->command->warn("New accounts use initial password: " . self::INITIAL_PASSWORD);
            $this->command->warn('All new accounts have force_password_reset = true. Staff must change password on first login.');
            $this->command->warn('Distribute credentials via your password manager — never by email or text.');
        }
    }
}
