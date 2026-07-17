<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const PURCHASE = 'purchase';
    public const SALES_ISSUE = 'sales_issue';
    public const PRODUCTION_ISSUE = 'production_issue';
    public const PRODUCTION_RECEIPT = 'production_receipt';
    public const ADJUSTMENT = 'adjustment';
    public const TRANSFER_IN = 'transfer_in';
    public const TRANSFER_OUT = 'transfer_out';

    public const IN = 'in';
    public const OUT = 'out';

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'warehouse_id', 'type', 'direction', 'quantity', 'unit_cost',
        'reference_type', 'reference_id', 'reference_number', 'notes', 'created_by', 'created_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $movement) {
            $movement->created_at ??= now();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
