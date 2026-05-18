<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceCatalog extends Model
{
    use HasFactory;

    protected $fillable = ['waste_bank_id', 'waste_category_id', 'price_per_kg', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function wasteBank(): BelongsTo
    {
        return $this->belongsTo(WasteBank::class);
    }

    public function wasteCategory(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class);
    }
}
