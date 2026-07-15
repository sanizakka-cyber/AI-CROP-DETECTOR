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
| Every account has force_password_reset = true so the user must change
| their password on first login.
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
            'password'    => '89931028Sa@',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'Abdulkadir',
            'last_name'   => 'Inda',
            'email'       => 'admin@msasagro.com',
            'phone'       => '08035558846',
            'role'        => 'admin',
            'department'  => 'Administration',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'Aisha',
            'middle_name' => 'Sabiu',
            'last_name'   => 'Bature',
            'email'       => 'finance@msasagro.com',
            'phone'       => '08137844133',
            'role'        => 'finance',
            'department'  => 'Finance',
            'password'    => 'password',
            'force_reset' => true,
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
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'Rabi',
            'last_name'   => 'Shehu',
            'email'       => 'agronomist@msasagro.com',
            'phone'       => '08037045668',
            'role'        => 'agronomist',
            'department'  => 'Agronomy',
            'specialization' => 'Crop Protection',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'Abbas',
            'last_name'   => 'Sani',
            'email'       => 'field@msasagro.com',
            'phone'       => '08160225001',
            'role'        => 'field-officer',
            'department'  => 'Field Operations',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'HR',
            'email'       => 'hr@msasagro.com',
            'phone'       => '08100000010',
            'role'        => 'hr',
            'department'  => 'Human Resources',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Operations',
            'email'       => 'operations@msasagro.com',
            'phone'       => '08100000011',
            'role'        => 'operations',
            'department'  => 'Operations',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Extension Officer',
            'email'       => 'extension@msasagro.com',
            'phone'       => '08100000012',
            'role'        => 'extension-officer',
            'department'  => 'Extension Services',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Dealer',
            'email'       => 'dealer@msasagro.com',
            'phone'       => '08100000013',
            'role'        => 'agro-dealer',
            'department'  => 'Agro-Input Supply',
            'organization'=> 'MSAS AgroShop',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Support',
            'email'       => 'support@msasagro.com',
            'phone'       => '08100000014',
            'role'        => 'customer-support',
            'department'  => 'Customer Support',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'Data Analyst',
            'email'       => 'data@msasagro.com',
            'phone'       => '08100000015',
            'role'        => 'data-analyst',
            'department'  => 'Data & Analytics',
            'password'    => 'password',
            'force_reset' => true,
        ],
        [
            'first_name'  => 'MSAS',
            'last_name'   => 'M&E Officer',
            'email'       => 'me@msasagro.com',
            'phone'       => '08100000016',
            'role'        => 'm-e-officer',
            'department'  => 'Monitoring & Evaluation',
            'password'    => 'password',
            'force_reset' => true,
        ],
    ];

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        foreach (self::STAFF as $spec) {
            $data = array_filter([
                'first_name'           => $spec['first_name'],
                'middle_name'          => $spec['middle_name'] ?? null,
                'last_name'            => $spec['last_name'],
                'phone'                => $spec['phone'],
                'password'             => Hash::make($spec['password']),
                'role'                 => $spec['role'],
                'department'           => $spec['department'] ?? null,
                'specialization'       => $spec['specialization'] ?? null,
                'organization'         => $spec['organization'] ?? null,
                'is_verified'          => true,
                'is_active'            => true,
                'force_password_reset' => $spec['force_reset'],
                'state'                => 'Katsina',
                'language'             => 'en',
            ], fn($v) => $v !== null);

            $existing = User::where('email', $spec['email'])->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                User::create(array_merge($data, ['email' => $spec['email']]));
                $created++;
            }
        }

        $this->command->info("✅ Staff accounts: {$created} created, {$updated} updated.");
        $this->command->warn('⚠  All accounts have force_password_reset = true. Staff must change password on first login.');
        $this->command->warn('   Distribute credentials via your password manager — never by email or text.');
    }
}
