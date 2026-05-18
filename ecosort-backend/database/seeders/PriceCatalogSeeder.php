<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceCatalog;
use App\Models\WasteBank;
use App\Models\WasteCategory;

class PriceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $banks = WasteBank::all();
        $categories = WasteCategory::all();

        $basePrices = [
            'paper' => 1500,
            'cardboard' => 1200,
            'biological' => 500,
            'metal' => 5000,
            'plastic' => 2000,
            'green-glass' => 800,
            'brown-glass' => 800,
            'white-glass' => 1000,
            'clothes' => 2500,
            'shoes' => 1500,
            'batteries' => 3000,
            'trash' => 0, // usually not bought, but just in case
        ];

        foreach ($banks as $bank) {
            foreach ($categories as $category) {
                $variation = rand(-200, 500); 
                // Randomly set active or inactive (mostly active)
                $isActive = rand(1, 10) > 3; // 70% active
                
                // For trash, give it a small default value instead of 0
                $basePrice = $category->name === 'trash' ? 200 : $basePrices[$category->name];

                PriceCatalog::create([
                    'waste_bank_id' => $bank->id,
                    'waste_category_id' => $category->id,
                    'price_per_kg' => max(100, $basePrice + $variation),
                    'is_active' => $isActive,
                ]);
            }
        }
    }
}
