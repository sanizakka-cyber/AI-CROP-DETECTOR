<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Sani Yawale Zakka',
                'email'    => 'ceo@msas.ng',
                'phone'    => '08032459879',
                'password' => Hash::make('password'),
                'role'     => 'ceo',
                'language' => 'en',
                'state'    => 'Katsina',
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'System Admin',
                'email'    => 'admin@msas.ng',
                'phone'    => '08000000001',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'language' => 'en',
                'state'    => 'Katsina',
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'Dr. Ibrahim Sule',
                'email'    => 'vet@msas.ng',
                'phone'    => '07011111111',
                'password' => Hash::make('password'),
                'role'     => 'vet',
                'language' => 'en',
                'state'    => 'Katsina',
                'specialization'    => 'Cattle',
                'years_experience'  => 8,
                'consultation_fee'  => 2000,
                'license_number'    => 'VCMN/2018/0042',
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'Aisha Crop Expert',
                'email'    => 'agro@msas.ng',
                'phone'    => '07022222222',
                'password' => Hash::make('password'),
                'role'     => 'agronomist',
                'language' => 'ha',
                'state'    => 'Katsina',
                'specialization'   => 'Crop Protection',
                'years_experience' => 5,
                'consultation_fee' => 1500,
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'Aminu Yusuf Katsina',
                'email'    => 'farmer@msas.ng',
                'phone'    => '08012345678',
                'password' => Hash::make('farmer123'),
                'role'     => 'farmer',
                'language' => 'ha',
                'state'    => 'Katsina',
                'lga'      => 'Katsina Central',
                'village'  => 'Kaura',
                'farm_size'=> 4.5,
                'crops_grown'      => json_encode(['Maize','Sorghum','Tomato']),
                'livestock_counts' => json_encode(['cattle'=>8,'goats'=>24,'sheep'=>12,'poultry'=>50]),
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'HR Manager',
                'email'    => 'hr@msas.ng',
                'phone'    => '08033333333',
                'password' => Hash::make('password'),
                'role'     => 'hr',
                'language' => 'en',
                'state'    => 'Katsina',
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'Finance Officer',
                'email'    => 'finance@msas.ng',
                'phone'    => '08044444444',
                'password' => Hash::make('password'),
                'role'     => 'finance',
                'language' => 'en',
                'state'    => 'Katsina',
                'is_verified' => true,
                'is_active'   => true,
            ],
            [
                'name'     => 'Malam Dealer',
                'email'    => 'dealer@msas.ng',
                'phone'    => '08055555555',
                'password' => Hash::make('password'),
                'role'     => 'agro-dealer',
                'language' => 'en',
                'state'    => 'Katsina',
                'organization'  => 'AgroMart Supplies',
                'is_verified'   => true,
                'is_active'     => true,
            ],
            [
                'name'     => 'Extension Officer Bello',
                'email'    => 'ext@msas.ng',
                'phone'    => '08066666666',
                'password' => Hash::make('password'),
                'role'     => 'extension-officer',
                'language' => 'ha',
                'state'    => 'Katsina',
                'lga'      => 'Funtua',
                'is_verified' => true,
                'is_active'   => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }

        // Seed sample animals
        $farmer = User::where('email','farmer@msas.ng')->first();
        if ($farmer) {
            $animals = [
                ['tag_no'=>'CT-001','type'=>'cattle','breed'=>'Fulani','sex'=>'male','weight_kg'=>320,'health_status'=>'healthy','cost_price'=>180000],
                ['tag_no'=>'CT-002','type'=>'cattle','breed'=>'Bunaji','sex'=>'female','weight_kg'=>260,'health_status'=>'sick','cost_price'=>150000],
                ['tag_no'=>'GT-001','type'=>'goat','breed'=>'Red Sokoto','sex'=>'male','weight_kg'=>18,'health_status'=>'recovering','cost_price'=>25000],
                ['tag_no'=>'SH-001','type'=>'sheep','breed'=>'Uda','sex'=>'male','weight_kg'=>35,'health_status'=>'healthy','cost_price'=>40000],
            ];
            foreach ($animals as $a) {
                $farmer->animals()->updateOrCreate(['tag_no'=>$a['tag_no']], $a);
            }

            // Seed sample finances
            $finances = [
                ['type'=>'Income','category'=>'Eggs','amount'=>45000,'transaction_date'=>now()->subDays(5),'description'=>'Egg sales - 450 crates'],
                ['type'=>'Income','category'=>'Sales','amount'=>180000,'transaction_date'=>now()->subDays(10),'description'=>'Ram sale'],
                ['type'=>'Expense','category'=>'Feed','amount'=>35000,'transaction_date'=>now()->subDays(3),'description'=>'Poultry feed purchase'],
                ['type'=>'Expense','category'=>'Vet','amount'=>8000,'transaction_date'=>now()->subDays(7),'description'=>'Veterinary treatment - Cow Maje'],
            ];
            foreach ($finances as $f) {
                $farmer->finances()->create($f);
            }
        }

        $this->command->info('✅ MSAS database seeded successfully!');
        $this->command->table(
            ['Role', 'Email', 'Login Method'],
            [
                ['CEO',              'ceo@msas.ng',    'Email + password (see .env.seed)'],
                ['Admin',            'admin@msas.ng',  'Email + password (see .env.seed)'],
                ['Vet',              'vet@msas.ng',    'Email + password (see .env.seed)'],
                ['Agronomist',       'agro@msas.ng',   'Email + password (see .env.seed)'],
                ['Farmer',           'farmer@msas.ng', 'Phone + password (see .env.seed)'],
                ['Agro-Dealer',      'dealer@msas.ng', 'Email + password (see .env.seed)'],
                ['Extension Officer','ext@msas.ng',    'Email + password (see .env.seed)'],
                ['HR',               'hr@msas.ng',     'Email + password (see .env.seed)'],
                ['Finance',          'finance@msas.ng','Email + password (see .env.seed)'],
            ]
        );
        $this->command->warn('⚠  Credentials are NOT shown here. Retrieve them from .env.seed or your password manager.');
    }
}
