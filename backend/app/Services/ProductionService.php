<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Implements the Production <-> Warehouse handshake described in the spec:
 *   - Starting an order plans the raw materials required from the BOM.
 *   - Completing an order automatically deducts raw materials from stock and
 *     increases the finished-goods stock, then posts the matching journal
 *     entry (raw materials inventory -> finished goods inventory).
 */
class ProductionService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AccountingService $accounting,
    ) {
    }

    public function createOrder(
        int $finishedProductId,
        float $quantityToProduce,
        int $rawWarehouseId,
        int $finishedWarehouseId,
        ?int $wipWarehouseId,
        User $productionUser,
        ?string $notes = null,
    ): ProductionOrder {
        return DB::transaction(function () use ($finishedProductId, $quantityToProduce, $rawWarehouseId, $finishedWarehouseId, $wipWarehouseId, $productionUser, $notes) {
            $order = ProductionOrder::create([
                'order_number' => 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'finished_product_id' => $finishedProductId,
                'quantity_to_produce' => $quantityToProduce,
                'status' => ProductionOrder::DRAFT,
                'raw_warehouse_id' => $rawWarehouseId,
                'wip_warehouse_id' => $wipWarehouseId,
                'finished_warehouse_id' => $finishedWarehouseId,
                'requested_by' => $productionUser->id,
                'notes' => $notes,
            ]);

            $bomLines = BillOfMaterial::where('finished_product_id', $finishedProductId)->get();

            foreach ($bomLines as $bom) {
                $planned = (float) $bom->quantity_required * $quantityToProduce * (1 + (float) $bom->scrap_percentage / 100);

                ProductionOrderMaterial::create([
                    'production_order_id' => $order->id,
                    'raw_material_id' => $bom->raw_material_id,
                    'planned_quantity' => $planned,
                    'unit' => $bom->unit,
                ]);
            }

            return $order->load('materials.rawMaterial', 'finishedProduct');
        });
    }

    public function startOrder(ProductionOrder $order): ProductionOrder
    {
        if ($order->status !== ProductionOrder::DRAFT) {
            throw new \RuntimeException('لا يمكن بدء أمر تصنيع ليس في حالة مسودة.');
        }

        $order->update(['status' => ProductionOrder::IN_PROGRESS, 'started_at' => now()]);

        return $order;
    }

    /**
     * Finish the order: consume raw materials (checked against the BOM) and
     * receive the finished good into stock, then post the accounting entry.
     */
    public function completeOrder(ProductionOrder $order, User $productionUser): ProductionOrder
    {
        if (! in_array($order->status, [ProductionOrder::DRAFT, ProductionOrder::IN_PROGRESS], true)) {
            throw new \RuntimeException('أمر التصنيع منتهي أو ملغي بالفعل.');
        }

        return DB::transaction(function () use ($order, $productionUser) {
            $materials = $order->materials()->with('rawMaterial')->get();
            $shortages = [];

            foreach ($materials as $material) {
                $available = $this->inventory->availableQuantity($material->raw_material_id, $order->raw_warehouse_id);
                if ($available < (float) $material->planned_quantity) {
                    $shortages[] = [
                        'product_id' => $material->raw_material_id,
                        'warehouse_id' => $order->raw_warehouse_id,
                        'available' => $available,
                        'requested' => (float) $material->planned_quantity,
                    ];
                }
            }

            if (! empty($shortages)) {
                throw new InsufficientStockException('لا توجد كمية كافية من المواد الخام لإتمام أمر التصنيع.', $shortages);
            }

            $totalRawMaterialCost = 0;

            foreach ($materials as $material) {
                $movement = $this->inventory->decrease(
                    productId: $material->raw_material_id,
                    warehouseId: $order->raw_warehouse_id,
                    quantity: (float) $material->planned_quantity,
                    type: StockMovement::PRODUCTION_ISSUE,
                    unitCost: $material->rawMaterial->cost_price,
                    referenceType: 'production_order',
                    referenceId: $order->id,
                    referenceNumber: $order->order_number,
                    actor: $productionUser,
                );

                $material->update(['consumed_quantity' => $material->planned_quantity]);
                $totalRawMaterialCost += (float) $material->planned_quantity * (float) $material->rawMaterial->cost_price;
            }

            // Increase finished goods stock, valued at the raw material cost consumed
            // (simple standard-costing approach: unit cost = total raw cost / qty produced).
            $unitCost = $order->quantity_to_produce > 0
                ? $totalRawMaterialCost / (float) $order->quantity_to_produce
                : 0;

            $this->inventory->increase(
                productId: $order->finished_product_id,
                warehouseId: $order->finished_warehouse_id,
                quantity: (float) $order->quantity_to_produce,
                type: StockMovement::PRODUCTION_RECEIPT,
                unitCost: $unitCost,
                referenceType: 'production_order',
                referenceId: $order->id,
                referenceNumber: $order->order_number,
                actor: $productionUser,
            );

            $order->update([
                'status' => ProductionOrder::COMPLETED,
                'completed_at' => now(),
                'started_at' => $order->started_at ?? now(),
            ]);

            $this->accounting->postProductionCompletion($order->order_number, $order->id, $totalRawMaterialCost, $productionUser);

            return $order->fresh(['materials.rawMaterial', 'finishedProduct']);
        });
    }
}
