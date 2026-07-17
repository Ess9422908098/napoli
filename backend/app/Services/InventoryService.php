<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for reading/mutating stock balances.
 * Every other service (Sales, Production, Purchase) must go through this
 * class instead of touching ProductStock/StockMovement directly, so that
 * "quantity on hand" and "reserved quantity" always stay consistent and the
 * audit ledger (stock_movements) is always kept in sync with the balances.
 */
class InventoryService
{
    /** Locks (or creates) the balance row for update inside the current transaction. */
    public function lockStock(int $productId, int $warehouseId): ProductStock
    {
        return ProductStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first() ?? ProductStock::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);
    }

    public function availableQuantity(int $productId, int $warehouseId): float
    {
        $stock = ProductStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? (float) $stock->quantity - (float) $stock->reserved_quantity : 0.0;
    }

    /** Reserve quantity (e.g. a pending sales invoice) without moving physical stock yet. */
    public function reserve(int $productId, int $warehouseId, float $quantity): ProductStock
    {
        $stock = $this->lockStock($productId, $warehouseId);
        $stock->reserved_quantity += $quantity;
        $stock->save();

        return $stock;
    }

    public function releaseReservation(int $productId, int $warehouseId, float $quantity): ProductStock
    {
        $stock = $this->lockStock($productId, $warehouseId);
        $stock->reserved_quantity = max(0, $stock->reserved_quantity - $quantity);
        $stock->save();

        return $stock;
    }

    /**
     * Physically increase stock (purchases, production output) and log the movement.
     */
    public function increase(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $type,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?User $actor = null,
    ): StockMovement {
        $stock = $this->lockStock($productId, $warehouseId);
        $stock->quantity += $quantity;
        $stock->save();

        return StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'direction' => StockMovement::IN,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * Physically decrease stock (sales fulfillment, production consumption) and log the
     * movement. Also releases any matching reservation. Throws if not enough physical stock.
     */
    public function decrease(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $type,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?User $actor = null,
        bool $releaseReservationToo = false,
    ): StockMovement {
        $stock = $this->lockStock($productId, $warehouseId);

        if ((float) $stock->quantity < $quantity) {
            throw new \App\Exceptions\InsufficientStockException(
                "الكمية المتاحة غير كافية للمنتج رقم {$productId} في المخزن رقم {$warehouseId}",
                [[
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'available' => (float) $stock->quantity,
                    'requested' => $quantity,
                ]]
            );
        }

        $stock->quantity -= $quantity;

        if ($releaseReservationToo) {
            $stock->reserved_quantity = max(0, $stock->reserved_quantity - $quantity);
        }

        $stock->save();

        return StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'direction' => StockMovement::OUT,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * Storekeeper physical count reconciliation: sets quantity to the counted value and
     * records the variance as an adjustment movement. Requires a reference/permit number.
     */
    public function adjustToCountedQuantity(
        int $productId,
        int $warehouseId,
        float $countedQuantity,
        string $referenceNumber,
        ?string $notes,
        User $actor,
    ): StockMovement {
        return DB::transaction(function () use ($productId, $warehouseId, $countedQuantity, $referenceNumber, $notes, $actor) {
            $stock = $this->lockStock($productId, $warehouseId);
            $variance = $countedQuantity - (float) $stock->quantity;
            $stock->quantity = $countedQuantity;
            $stock->save();

            return StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => StockMovement::ADJUSTMENT,
                'direction' => $variance >= 0 ? StockMovement::IN : StockMovement::OUT,
                'quantity' => abs($variance),
                'reference_type' => 'adjustment',
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);
        });
    }
}
