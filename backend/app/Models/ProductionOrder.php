<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use HasFactory;

    public const DRAFT = 'draft';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number', 'finished_product_id', 'quantity_to_produce', 'status',
        'raw_warehouse_id', 'wip_warehouse_id', 'finished_warehouse_id',
        'requested_by', 'started_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'quantity_to_produce' => 'decimal:4',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'finished_product_id');
    }

    public function rawWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'raw_warehouse_id');
    }

    public function wipWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'wip_warehouse_id');
    }

    public function finishedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'finished_warehouse_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }
}
