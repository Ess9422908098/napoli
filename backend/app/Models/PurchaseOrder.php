<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const RECEIVED = 'received';
    public const CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'order_number', 'supplier_id', 'warehouse_id', 'status',
        'created_by', 'total_amount', 'order_date', 'received_at',
        'payment_status', 'paid_amount', 'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'received_at' => 'datetime',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $attributes = [
        'payment_status' => self::PAYMENT_PENDING,
        'paid_amount' => 0,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
