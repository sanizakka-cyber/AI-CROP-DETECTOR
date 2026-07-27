<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OfficialAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ── Internal Staff ────────────────────────────────────────────────
            ['email'=>'admin@msasagro.com',               'first_name'=>'Operations',    'last_name'=>'Admin',               'phone'=>'+2348020000001', 'role'=>'admin',                'state'=>'Abuja'],
            ['email'=>'hr@msasagro.com',                  'first_name'=>'HR',            'last_name'=>'Manager',             'phone'=>'+2348020000002', 'role'=>'hr',                   'state'=>'Abuja'],
            ['email'=>'finance@msasagro.com',             'first_name'=>'Finance',       'last_name'=>'Officer',             'phone'=>'+2348020000003', 'role'=>'finance',              'state'=>'Abuja'],
            ['email'=>'support@msasagro.com',             'first_name'=>'Customer',      'last_name'=>'Support',             'phone'=>'+2348020000004', 'role'=>'customer-support',     'state'=>'Abuja'],
            ['email'=>'marketplace@msasagro.com',         'first_name'=>'Marketplace',   'last_name'=>'Manager',             'phone'=>'+2348020000005', 'role'=>'operations',           'state'=>'Abuja'],
            ['email'=>'verification@msasagro.com',        'first_name'=>'Verification',  'last_name'=>'Officer',             'phone'=>'+2348020000006', 'role'=>'operations',           'state'=>'Abuja'],
            ['email'=>'consultation@msasagro.com',        'first_name'=>'Consultation',  'last_name'=>'Coordinator',         'phone'=>'+2348020000007', 'role'=>'operations',           'state'=>'Abuja'],
            ['email'=>'logisticsmanager@msasagro.com',    'first_name'=>'Logistics',     'last_name'=>'Manager',             'phone'=>'+2348020000008', 'role'=>'logistics-provider',   'state'=>'Lagos'],
            ['email'=>'riders@msasagro.com',              'first_name'=>'Rider',         'last_name'=>'Supervisor',          'phone'=>'+2348020000009', 'role'=>'operations',           'state'=>'Lagos'],
            ['email'=>'analytics@msasagro.com',           'first_name'=>'Analytics',     'last_name'=>'Officer',             'phone'=>'+2348020000010', 'role'=>'data-analyst',         'state'=>'Abuja'],
            ['email'=>'it@msasagro.com',                  'first_name'=>'IT',            'last_name'=>'Administrator',       'phone'=>'+2348020000011', 'role'=>'admin',                'state'=>'Abuja'],

            // ── Platform Role Accounts ─────────────────────────────────────────
            ['email'=>'farmer@msasagro.com',              'first_name'=>'Demo',          'last_name'=>'Farmer',              'phone'=>'+2348030000001', 'role'=>'farmer',               'state'=>'Kano'],
            ['email'=>'livestock@msasagro.com',           'first_name'=>'Demo',          'last_name'=>'Livestock Farmer',    'phone'=>'+2348030000002', 'role'=>'farmer',               'state'=>'Kaduna'],
            ['email'=>'poultry@msasagro.com',             'first_name'=>'Demo',          'last_name'=>'Poultry Farmer',      'phone'=>'+2348030000003', 'role'=>'farmer',               'state'=>'Oyo'],
            ['email'=>'agronomist@msasagro.com',          'first_name'=>'Demo',          'last_name'=>'Agronomist',          'phone'=>'+2348030000004', 'role'=>'agronomist',           'state'=>'Katsina', 'specialization'=>'Crop Science', 'years_experience'=>8, 'consultation_fee'=>0],
            ['email'=>'veterinarian@msasagro.com',        'first_name'=>'Demo',          'last_name'=>'Veterinarian',        'phone'=>'+2348030000005', 'role'=>'vet',                  'state'=>'Katsina', 'specialization'=>'Large Animal Medicine', 'years_experience'=>6, 'consultation_fee'=>0, 'license_number'=>'VET-DEMO-001'],
            ['email'=>'agrodealer@msasagro.com',          'first_name'=>'Demo',          'last_name'=>'Agro Dealer',         'phone'=>'+2348030000006', 'role'=>'agro-dealer',          'state'=>'Lagos',   'organization'=>'MSAS Demo AgroShop'],
            ['email'=>'equipmentdealer@msasagro.com',     'first_name'=>'Demo',          'last_name'=>'Equipment Dealer',    'phone'=>'+2348030000007', 'role'=>'equipment-dealer',     'state'=>'Lagos',   'organization'=>'MSAS Demo Equipment Ltd'],
            ['email'=>'agribusiness@msasagro.com',        'first_name'=>'Demo',          'last_name'=>'Agribusiness',        'phone'=>'+2348030000008', 'role'=>'agribusiness-owner',   'state'=>'Oyo',     'organization'=>'MSAS Demo AgriBusiness'],
            ['email'=>'cooperative@msasagro.com',         'first_name'=>'Demo',          'last_name'=>'Cooperative',         'phone'=>'+2348030000009', 'role'=>'cooperative',          'state'=>'Benue',   'organization'=>'MSAS Demo Farmers Cooperative'],
            ['email'=>'supplier@msasagro.com',            'first_name'=>'Demo',          'last_name'=>'Supplier',            'phone'=>'+2348030000010', 'role'=>'input-supplier',       'state'=>'Rivers',  'organization'=>'MSAS Demo Inputs Ltd'],
            ['email'=>'logistics@msasagro.com',           'first_name'=>'Demo',          'last_name'=>'Rider',               'phone'=>'+2348030000011', 'role'=>'rider',                'state'=>'Lagos',   'vehicle_type'=>'motorcycle', 'rider_status'=>'available'],
            ['email'=>'investor@msasagro.com',            'first_name'=>'Demo',          'last_name'=>'Investor',            'phone'=>'+2348030000012', 'role'=>'investor',             'state'=>'Lagos',   'organization'=>'MSAS Demo Capital'],
            ['email'=>'ngo@msasagro.com',                 'first_name'=>'Demo',          'last_name'=>'NGO',                 'phone'=>'+2348030000013', 'role'=>'ngo',                  'state'=>'Borno',   'organization'=>'MSAS Demo AgriRelief'],
            ['email'=>'government@msasagro.com',          'first_name'=>'Demo',          'last_name'=>'Government',          'phone'=>'+2348030000014', 'role'=>'government-agency',    'state'=>'Abuja',   'organization'=>'MSAS Demo FMARD'],
            ['email'=>'researchinstitution@msasagro.com', 'first_name'=>'Demo',          'last_name'=>'Research Institution','phone'=>'+2348030000015', 'role'=>'research-institution', 'state'=>'Ibadan',  'organization'=>'MSAS Demo ARI'],
            ['email'=>'researcher@msasagro.com',          'first_name'=>'Demo',          'last_name'=>'Researcher',          'phone'=>'+2348030000016', 'role'=>'researcher',           'state'=>'Ibadan'],
            ['email'=>'student@msasagro.com',             'first_name'=>'Demo',          'last_name'=>'Student',             'phone'=>'+2348030000017', 'role'=>'student',              'state'=>'Enugu'],
            ['email'=>'generaluser@msasagro.com',         'first_name'=>'Demo',          'last_name'=>'General User',        'phone'=>'+2348030000018', 'role'=>'general-user',         'state'=>'Lagos'],
        ];

        $pw      = Hash::make('Msas@2026');
        $created = 0;
        $updated = 0;

        foreach ($accounts as $spec) {
            $data = array_filter([
                'first_name'           => $spec['first_name'],
                'last_name'            => $spec['last_name'],
                'phone'                => $spec['phone'],
                'role'                 => $spec['role'],
                'password'             => $pw,
                'state'                => $spec['state']            ?? null,
                'organization'         => $spec['organization']     ?? null,
                'specialization'       => $spec['specialization']   ?? null,
                'years_experience'     => $spec['years_experience'] ?? null,
                'consultation_fee'     => $spec['consultation_fee'] ?? null,
                'license_number'       => $spec['license_number']   ?? null,
                'vehicle_type'         => $spec['vehicle_type']     ?? null,
                'rider_status'         => $spec['rider_status']     ?? null,
                'language'             => 'en',
                'is_active'            => true,
                'is_verified'          => true,
                'email_verified_at'    => now(),
                'application_status'   => 'approved',
                'force_password_reset' => false,
                'is_test_account'      => false,
            ], fn($v) => $v !== null);

            $existing = User::where('email', $spec['email'])->first();
            if ($existing) {
                $existing->update($data);
                $this->command->line("  <comment>updated</comment>  {$spec['email']} ({$spec['role']})");
                $updated++;
            } else {
                $data['email'] = $spec['email'];
                User::create($data);
                $this->command->line("  <info>created</info>  {$spec['email']} ({$spec['role']})");
                $created++;
            }
        }

        $this->command->info("\n✅ Done — {$created} created, {$updated} updated.");
        $this->command->warn('   All passwords: Msas@2026');
        $this->command->warn('   CEO account must be set separately (see instructions).');
    }
}
