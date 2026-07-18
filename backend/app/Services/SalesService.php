<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Implements the Sales <-> Storekeeper handshake described in the spec:
 *   1. Sales creates an invoice -> stock availability is checked first.
 *   2. If enough stock exists, the quantity is RESERVED (not yet removed).
 *   3. The storekeeper is notified automatically to prepare the order.
 *   4. Only when the storekeeper fulfills the invoice is stock physically
 *      deducted and the COGS journal entry posted.
 */
class SalesService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AccountingService $accounting,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * @param array<int, array{product_id: int, warehouse_id: int, quantity: float, unit_price: float}> $items
     */
    public function createInvoice(array $items, ?int $customerId, User $salesUser, ?string $notes = null): SalesInvoice
    {
        return DB::transaction(function () use ($items, $customerId, $salesUser, $notes) {
            $shortages = [];

            // Step 1: verify availability for every line BEFORE reserving anything.
            foreach ($items as $item) {
                $available = $this->inventory->availableQuantity($item['product_id'], $item['warehouse_id']);
                if ($available < $item['quantity']) {
                    $shortages[] = [
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'],
                        'available' => $available,
                        'requested' => $item['quantity'],
                    ];
                }
            }

            if (! empty($shortages)) {
                throw new InsufficientStockException('الكمية المتاحة في المخزون غير كافية لإتمام الفاتورة.', $shortages);
            }

            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $invoice = SalesInvoice::create([
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'customer_id' => $customerId,
                'created_by' => $salesUser->id,
                'status' => SalesInvoice::PENDING_FULFILLMENT,
                'approval_status' => SalesInvoice::PENDING_APPROVAL,
                'total_amount' => $totalAmount,
                'notes' => $notes,
            ]);

            // Step 2: reserve the stock so no one else can oversell it.
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $this->inventory->reserve($item['product_id'], $item['warehouse_id'], $item['quantity']);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $product->cost_price,
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // Step 3: invoice request is sent to the accountant for approval.
            $this->notifications->notifyAccountantsOfPendingInvoice($invoice->invoice_number, $invoice->id);

            return $invoice->load('items.product', 'items.warehouse', 'customer');
        });
    }

    /** Storekeeper action: physically ships the reserved items and closes the invoice. */
    public function approveInvoice(SalesInvoice $invoice, User $accountant): SalesInvoice
    {
        if ($invoice->status !== SalesInvoice::PENDING_FULFILLMENT || $invoice->approval_status !== 'pending') {
            throw new \RuntimeException('الفاتورة غير مؤهلة للاعتماد أو تمت معالجتها بالفعل.');
        }

        return DB::transaction(function () use ($invoice, $accountant) {
            $invoice->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $accountant->id,
            ]);

            $this->accounting->postSalesRevenue($invoice->invoice_number, $invoice->id, (float) $invoice->total_amount, $accountant);
            $this->notifications->notifyStorekeepersOfApprovedInvoice($invoice->invoice_number, $invoice->id);

            return $invoice->fresh(['items.product', 'items.warehouse', 'customer', 'creator', 'approver']);
        });
    }

    public function fulfillInvoice(SalesInvoice $invoice, User $storekeeper): SalesInvoice
    {
        if ($invoice->status !== SalesInvoice::PENDING_FULFILLMENT || $invoice->approval_status !== SalesInvoice::APPROVED) {
            throw new \RuntimeException('يجب اعتماد الفاتورة من المحاسب قبل التجهيز.');
        }

        return DB::transaction(function () use ($invoice, $storekeeper) {
            $totalCost = 0;

            foreach ($invoice->items()->with('product')->get() as $item) {
                $this->inventory->decrease(
                    productId: $item->product_id,
                    warehouseId: $item->warehouse_id,
                    quantity: (float) $item->quantity,
                    type: StockMovement::SALES_ISSUE,
                    unitCost: (float) $item->unit_cost,
                    referenceType: 'sales_invoice',
                    referenceId: $invoice->id,
                    referenceNumber: $invoice->invoice_number,
                    actor: $storekeeper,
                    releaseReservationToo: true,
                );

                $item->update(['is_fulfilled' => true]);
                $totalCost += (float) $item->quantity * (float) $item->unit_cost;
            }

            $invoice->update([
                'status' => SalesInvoice::FULFILLED,
                'fulfilled_at' => now(),
                'fulfilled_by' => $storekeeper->id,
            ]);

            $this->accounting->postCostOfGoodsSold($invoice->invoice_number, $invoice->id, $totalCost, $storekeeper);

            return $invoice->fresh(['items.product', 'items.warehouse', 'customer']);
        });
    }

    public function cancelInvoice(SalesInvoice $invoice): SalesInvoice
    {
        if ($invoice->status !== SalesInvoice::PENDING_FULFILLMENT) {
            throw new \RuntimeException('لا يمكن إلغاء فاتورة تم تجهيزها بالفعل.');
        }

        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                $this->inventory->releaseReservation($item->product_id, $item->warehouse_id, (float) $item->quantity);
            }

            $invoice->update(['status' => SalesInvoice::CANCELLED]);

            return $invoice->fresh(['items.product', 'items.warehouse']);
        });
    }
}
