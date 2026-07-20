<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'msaslivestockagroservices@gmail.com'],
            [
                'first_name'           => 'Sani',
                'middle_name'          => 'Zakka',
                'last_name'            => 'Yawale',
                'password'             => Hash::make('Admin@12345'),
                'role'                 => 'ceo',
                'is_verified'          => true,
                'is_active'            => true,
                'force_password_reset' => true,
                'is_test_account'      => false,
                'country'              => 'Nigeria',
                'language'             => 'en',
                'state'                => 'Katsina',
            ]
        );
    }
}
