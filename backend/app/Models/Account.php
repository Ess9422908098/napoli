<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    public const ASSET = 'asset';
    public const LIABILITY = 'liability';
    public const EQUITY = 'equity';
    public const REVENUE = 'revenue';
    public const EXPENSE = 'expense';

    // Standard chart-of-accounts codes used by AccountingService.
    public const CASH = '1000';
    public const ACCOUNTS_RECEIVABLE = '1100';
    public const RAW_MATERIALS_INVENTORY = '1200';
    public const FINISHED_GOODS_INVENTORY = '1300';
    public const ACCOUNTS_PAYABLE = '2000';
    public const SALES_REVENUE = '4000';
    public const COST_OF_GOODS_SOLD = '5000';
    public const PAYROLL_EXPENSE = '5100';

    protected $fillable = ['code', 'name', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
