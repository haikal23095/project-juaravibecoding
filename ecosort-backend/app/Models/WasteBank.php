<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id', 'name', 'address', 'latitude', 'longitude', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function priceCatalogs(): HasMany
    {
        return $this->hasMany(PriceCatalog::class);
    }

    public function wasteCategories(): HasMany
    {
        return $this->hasMany(WasteCategory::class, 'waste_bank_id');
    }

    protected static function booted()
    {
        static::created(function ($wasteBank) {
            $defaultCategories = \App\Models\WasteCategory::whereNull('waste_bank_id')->get();
            
            if ($defaultCategories->isEmpty()) {
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
                foreach ($categories as $cat) {
                    \App\Models\WasteCategory::create(array_merge($cat, [
                        'waste_bank_id' => $wasteBank->id,
                        'is_default' => false,
                    ]));
                }
            } else {
                foreach ($defaultCategories as $cat) {
                    \App\Models\WasteCategory::create([
                        'waste_bank_id' => $wasteBank->id,
                        'name' => $cat->name,
                        'description' => $cat->description,
                        'icon_url' => $cat->icon_url,
                        'is_default' => false,
                        'is_active' => $cat->is_active,
                    ]);
                }
            }
        });
    }
}
