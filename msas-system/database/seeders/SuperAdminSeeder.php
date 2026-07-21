<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $existing = User::where('email', 'msaslivestockagroservices@gmail.com')->first();

        if ($existing) {
            // Re-runs of this seeder must NOT reset force_password_reset or password —
            // the CEO may have already changed their credentials via the UI.
            $existing->update([
                'first_name' => 'Sani',
                'middle_name' => 'Zakka',
                'last_name'  => 'Yawale',
                'role'       => 'ceo',
                'is_verified' => true,
                'is_active'   => true,
                'is_test_account' => false,
                'country'    => 'Nigeria',
                'language'   => 'en',
                'state'      => 'Katsina',
            ]);
            $this->command->info('CEO account already exists — profile updated, password/force_reset NOT changed.');
        } else {
            User::create([
                'first_name'           => 'Sani',
                'middle_name'          => 'Zakka',
                'last_name'            => 'Yawale',
                'email'                => 'msaslivestockagroservices@gmail.com',
                'password'             => Hash::make('Admin@12345'),
                'role'                 => 'ceo',
                'is_verified'          => true,
                'is_active'            => true,
                'force_password_reset' => true,
                'is_test_account'      => false,
                'country'              => 'Nigeria',
                'language'             => 'en',
                'state'                => 'Katsina',
            ]);
            $this->command->info('CEO account created with force_password_reset = true.');
        }
    }
}
