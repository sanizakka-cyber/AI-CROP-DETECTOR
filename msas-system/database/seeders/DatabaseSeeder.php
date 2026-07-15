<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductSeeder::class);

        // 1. CEO ACCOUNT
        User::create([
            'first_name' => 'Sani',
            'middle_name' => 'Yawale',
            'last_name' => 'Zakka',
            'email' => 'sanizakka@gmail.com',
            'phone' => '08032459879',
            'password' => Hash::make('password123'),
            'role' => 'ceo',
            'is_active' => true,
        ]);

        // 2. ADMIN ACCOUNT
        User::create([
            'first_name' => 'Abdulkadir',
            'middle_name' => null,
            'last_name' => 'Inda',
            'email' => 'admin@msas.com',
            'phone' => '08035558846',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. FINANCE OFFICER
        User::create([
            'first_name' => 'Aisha',
            'middle_name' => 'Sabiu',
            'last_name' => 'Bature',
            'email' => 'finance@msas.com',
            'phone' => '08137844133',
            'password' => Hash::make('password123'),
            'role' => 'finance',
            'is_active' => true,
        ]);

        // 4. VET DOCTOR
        User::create([
            'first_name' => 'Surajo',
            'middle_name' => 'Dutsin',
            'last_name' => 'Safe',
            'email' => 'vet@msas.com',
            'phone' => '08127878061',
            'password' => Hash::make('password123'),
            'role' => 'vet',
            'is_active' => true,
        ]);

        // 5. AGRONOMIST
        User::create([
            'first_name' => 'Rabi',
            'middle_name' => null,
            'last_name' => 'Shehu',
            'email' => 'agro@msas.com',
            'phone' => '08037045668',
            'password' => Hash::make('password123'),
            'role' => 'agronomist',
            'is_active' => true,
        ]);

        // 6. FIELD OFFICER
        User::create([
            'first_name' => 'Abbas',
            'middle_name' => null,
            'last_name' => 'Sani',
            'email' => 'field@msas.com',
            'phone' => '08160225001',
            'password' => Hash::make('password123'),
            'role' => 'field-officer',
            'is_active' => true,
        ]);

        // 7. GENERAL USER / FARMER
        User::create([
            'first_name' => 'Suleiman',
            'middle_name' => null,
            'last_name' => 'Garba',
            'email' => 'user@msas.com',
            'phone' => '07036601786',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'is_active' => true,
        ]);

        // Seed some sample animals for the farmer
        $farmer = User::where('email', 'user@msas.com')->first();
        if($farmer) {
            \App\Models\Animal::create([
                'user_id' => $farmer->id,
                'tag_number' => 'COW-001',
                'species' => 'Cattle',
                'breed' => 'Sokoto Gudali',
                'gender' => 'Female',
                'weight_kg' => 350
            ]);

            \App\Models\PoultryRecord::create([
                'user_id' => $farmer->id,
                'batch_number' => 'BATCH-A1',
                'bird_type' => 'Layers',
                'quantity' => 500,
                'date_acquired' => now()->subMonths(2)
            ]);

            \App\Models\Finance::create([
                'user_id' => $farmer->id,
                'type' => 'Income',
                'category' => 'Egg Sale',
                'amount' => 45000,
                'transaction_date' => now(),
            ]);
        }
    }
}
