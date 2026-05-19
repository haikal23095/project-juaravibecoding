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

        $user1 = User::create([
            'name' => "Reyhan Fauzan",
            'email' => "user1@ecosort.test",
            'password' => Hash::make('password'),
            'points' => 12450,
            'balance' => 245500.00,
            'scan_count' => 124,
        ]);
        $user1->assignRole('user');

        $user2 = User::create([
            'name' => "User 2",
            'email' => "user2@ecosort.test",
            'password' => Hash::make('password'),
            'points' => 5000,
            'balance' => 98000.00,
            'scan_count' => 48,
        ]);
        $user2->assignRole('user');

        $user3 = User::create([
            'name' => "User 3",
            'email' => "user3@ecosort.test",
            'password' => Hash::make('password'),
            'points' => 0,
            'balance' => 0.00,
            'scan_count' => 0,
        ]);
        $user3->assignRole('user');
    }
}
