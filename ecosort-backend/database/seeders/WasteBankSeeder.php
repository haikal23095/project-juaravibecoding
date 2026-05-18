<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteBank;
use App\Models\User;

class WasteBankSeeder extends Seeder
{
    public function run(): void
    {
        $managers = User::role('bank_sampah')->get();

        if ($managers->count() >= 2) {
            WasteBank::create([
                'manager_id' => $managers[0]->id,
                'name' => 'Bank Sampah Sejahtera',
                'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                'latitude' => -6.1884,
                'longitude' => 106.7648,
                'is_active' => true,
            ]);

            WasteBank::create([
                'manager_id' => $managers[1]->id,
                'name' => 'Bank Sampah Melati',
                'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
                'latitude' => -6.2088,
                'longitude' => 106.8229,
                'is_active' => true,
            ]);
        }
    }
}
