<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteCategory;

class WasteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'paper', 'description' => 'Kertas HVS, koran, dll.', 'icon_url' => 'icons/paper.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'cardboard', 'description' => 'Kardus dan karton.', 'icon_url' => 'icons/cardboard.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'biological', 'description' => 'Sampah organik/biologis.', 'icon_url' => 'icons/biological.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'metal', 'description' => 'Besi, aluminium, tembaga.', 'icon_url' => 'icons/metal.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'plastic', 'description' => 'Botol dan kemasan plastik.', 'icon_url' => 'icons/plastic.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'green-glass', 'description' => 'Kaca berwarna hijau.', 'icon_url' => 'icons/glass.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'brown-glass', 'description' => 'Kaca berwarna cokelat.', 'icon_url' => 'icons/glass.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'white-glass', 'description' => 'Kaca bening.', 'icon_url' => 'icons/glass.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'clothes', 'description' => 'Pakaian bekas.', 'icon_url' => 'icons/clothes.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'shoes', 'description' => 'Sepatu bekas.', 'icon_url' => 'icons/shoes.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'batteries', 'description' => 'Baterai bekas.', 'icon_url' => 'icons/battery.png', 'is_default' => true, 'is_active' => true],
            ['name' => 'trash', 'description' => 'Sampah campur/residu.', 'icon_url' => 'icons/trash.png', 'is_default' => true, 'is_active' => true],
        ];

        foreach ($categories as $category) {
            WasteCategory::create($category);
        }
    }
}
