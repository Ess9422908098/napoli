<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    public const RAW_MATERIALS = 'raw_materials';
    public const WIP = 'wip';
    public const FINISHED_GOODS = 'finished_goods';

    protected $fillable = ['code', 'name', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
