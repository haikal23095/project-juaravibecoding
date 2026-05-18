<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@ecosort.test',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super_admin');

        $manager1 = User::create([
            'name' => 'Manager Bank Sampah 1',
            'email' => 'manager1@ecosort.test',
            'password' => Hash::make('password'),
            'business_id' => 'NIB-1234567890',
        ]);
        $manager1->assignRole('bank_sampah');

        $manager2 = User::create([
            'name' => 'Manager Bank Sampah 2',
            'email' => 'manager2@ecosort.test',
            'password' => Hash::make('password'),
            'business_id' => 'NIB-0987654321',
        ]);
        $manager2->assignRole('bank_sampah');

        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@ecosort.test",
                'password' => Hash::make('password'),
            ]);
            $user->assignRole('user');
        }
    }
}
