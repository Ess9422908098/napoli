<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;

    public const PENDING_FULFILLMENT = 'pending_fulfillment';
    public const FULFILLED = 'fulfilled';
    public const CANCELLED = 'cancelled';

    public const PENDING_APPROVAL = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'invoice_number', 'customer_id', 'created_by', 'status',
        'approval_status', 'approved_by', 'approved_at',
        'total_amount', 'notes', 'fulfilled_at', 'fulfilled_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'fulfilled_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }
}
