<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * @param array<int, array{product_id: int, quantity: float, unit_cost: float}> $items
     */
    public function createOrder(int $supplierId, int $warehouseId, array $items, ?User $actor = null): PurchaseOrder
    {
        return DB::transaction(function () use ($supplierId, $warehouseId, $items, $actor) {
            $total = 0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['unit_cost'];
            }

            $order = PurchaseOrder::create([
                'order_number' => 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'status' => PurchaseOrder::SUBMITTED,
                'payment_status' => PurchaseOrder::PAYMENT_PENDING,
                'paid_amount' => 0,
                'created_by' => $actor?->id,
                'total_amount' => $total,
                'order_date' => now(),
            ]);

            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            return $order->load('items.product', 'supplier', 'warehouse');
        });
    }

    /** Storekeeper receives the goods: stock increases and a payable journal entry is posted. */
    public function receiveOrder(PurchaseOrder $order, User $storekeeper): PurchaseOrder
    {
        if ($order->status === PurchaseOrder::RECEIVED) {
            throw new \RuntimeException('أمر الشراء تم استلامه بالفعل.');
        }

        return DB::transaction(function () use ($order, $storekeeper) {
            foreach ($order->items()->with('product')->get() as $item) {
                $this->inventory->increase(
                    productId: $item->product_id,
                    warehouseId: $order->warehouse_id,
                    quantity: (float) $item->quantity,
                    type: StockMovement::PURCHASE,
                    unitCost: (float) $item->unit_cost,
                    referenceType: 'purchase_order',
                    referenceId: $order->id,
                    referenceNumber: $order->order_number,
                    actor: $storekeeper,
                );
            }

            $order->update(['status' => PurchaseOrder::RECEIVED, 'received_at' => now()]);

            $this->accounting->postPurchase($order->order_number, $order->id, (float) $order->total_amount, $storekeeper);

            return $order->fresh(['items.product', 'supplier', 'warehouse']);
        });
    }

    public function recordPayment(PurchaseOrder $order, float $amount, User $accountant): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::RECEIVED) {
            throw new \RuntimeException('لا يمكن تسجيل الدفع قبل استلام المواد.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('يجب أن تكون قيمة الدفع أكبر من صفر.');
        }

        $outstanding = (float) $order->total_amount - (float) $order->paid_amount;
        if ($amount > $outstanding) {
            throw new \InvalidArgumentException('مبلغ الدفع أكبر من الرصيد المتبقي.');
        }

        return DB::transaction(function () use ($order, $amount, $accountant, $outstanding) {
            $newPaid = (float) $order->paid_amount + $amount;
            $order->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newPaid >= (float) $order->total_amount ? PurchaseOrder::PAYMENT_PAID : PurchaseOrder::PAYMENT_PARTIAL,
                'paid_at' => now(),
            ]);

            $this->accounting->postPurchasePayment($order->order_number, $order->id, $amount, $accountant);

            return $order->fresh(['items.product', 'supplier', 'warehouse']);
        });
    }
}
