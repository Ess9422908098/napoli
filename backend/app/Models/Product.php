<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const RAW_MATERIAL = 'raw_material';
    public const FINISHED_GOOD = 'finished_good';

    protected $fillable = [
        'sku', 'name', 'barcode', 'type', 'unit',
        'cost_price', 'selling_price', 'reorder_level', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function bomLines(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'finished_product_id');
    }

    public function usedInBom(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'raw_material_id');
    }

    public function isRawMaterial(): bool
    {
        return $this->type === self::RAW_MATERIAL;
    }

    public function isFinishedGood(): bool
    {
        return $this->type === self::FINISHED_GOOD;
    }
}
