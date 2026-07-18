<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Every stock/sales/purchase/payroll movement in the system must produce a
 * balanced double-entry journal entry through this service, so the
 * Accountant role always sees an automatic, real-time financial trail
 * without manual bookkeeping.
 */
class AccountingService
{
    /**
     * @param array<int, array{account_code: string, debit?: float, credit?: float}> $lines
     */
    public function postEntry(
        string $description,
        array $lines,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?User $actor = null,
    ): JournalEntry {
        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('القيد المحاسبي غير متوازن: مجموع المدين يجب أن يساوي مجموع الدائن.');
        }

        return DB::transaction(function () use ($description, $lines, $referenceType, $referenceId, $actor) {
            $entry = JournalEntry::create([
                'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'entry_date' => now(),
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $actor?->id,
            ]);

            foreach ($lines as $line) {
                if (empty($line['debit']) && empty($line['credit'])) {
                    continue;
                }

                $account = Account::where('code', $line['account_code'])->firstOrFail();

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function postSalesRevenue(string $invoiceNumber, int $invoiceId, float $totalAmount, ?User $actor): JournalEntry
    {
        return $this->postEntry(
            "إيراد فاتورة بيع رقم {$invoiceNumber}",
            [
                ['account_code' => Account::ACCOUNTS_RECEIVABLE, 'debit' => $totalAmount],
                ['account_code' => Account::SALES_REVENUE, 'credit' => $totalAmount],
            ],
            referenceType: 'sales_invoice',
            referenceId: $invoiceId,
            actor: $actor,
        );
    }

    public function postCostOfGoodsSold(string $invoiceNumber, int $invoiceId, float $totalCost, ?User $actor): ?JournalEntry
    {
        if ($totalCost <= 0) {
            return null;
        }

        return $this->postEntry(
            "تكلفة البضاعة المباعة لفاتورة رقم {$invoiceNumber}",
            [
                ['account_code' => Account::COST_OF_GOODS_SOLD, 'debit' => $totalCost],
                ['account_code' => Account::FINISHED_GOODS_INVENTORY, 'credit' => $totalCost],
            ],
            referenceType: 'sales_invoice',
            referenceId: $invoiceId,
            actor: $actor,
        );
    }

    public function postPurchase(string $orderNumber, int $orderId, float $totalAmount, ?User $actor): JournalEntry
    {
        return $this->postEntry(
            "شراء مواد خام - أمر شراء رقم {$orderNumber}",
            [
                ['account_code' => Account::RAW_MATERIALS_INVENTORY, 'debit' => $totalAmount],
                ['account_code' => Account::ACCOUNTS_PAYABLE, 'credit' => $totalAmount],
            ],
            referenceType: 'purchase_order',
            referenceId: $orderId,
            actor: $actor,
        );
    }

    public function postPurchasePayment(string $orderNumber, int $orderId, float $amount, ?User $actor): JournalEntry
    {
        return $this->postEntry(
            "سداد فاتورة شراء رقم {$orderNumber}",
            [
                ['account_code' => Account::ACCOUNTS_PAYABLE, 'debit' => $amount],
                ['account_code' => Account::CASH, 'credit' => $amount],
            ],
            referenceType: 'purchase_order',
            referenceId: $orderId,
            actor: $actor,
        );
    }

    public function postProductionCompletion(string $orderNumber, int $orderId, float $rawMaterialCost, ?User $actor): ?JournalEntry
    {
        if ($rawMaterialCost <= 0) {
            return null;
        }

        return $this->postEntry(
            "إتمام أمر تصنيع رقم {$orderNumber} - تحويل مواد خام إلى منتج تام",
            [
                ['account_code' => Account::FINISHED_GOODS_INVENTORY, 'debit' => $rawMaterialCost],
                ['account_code' => Account::RAW_MATERIALS_INVENTORY, 'credit' => $rawMaterialCost],
            ],
            referenceType: 'production_order',
            referenceId: $orderId,
            actor: $actor,
        );
    }

    public function postPayroll(int $payrollId, string $employeeName, float $amount, ?User $actor): JournalEntry
    {
        return $this->postEntry(
            "مرتب الموظف {$employeeName}",
            [
                ['account_code' => Account::PAYROLL_EXPENSE, 'debit' => $amount],
                ['account_code' => Account::CASH, 'credit' => $amount],
            ],
            referenceType: 'payroll',
            referenceId: $payrollId,
            actor: $actor,
        );
    }
}
