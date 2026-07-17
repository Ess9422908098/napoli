<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

/**
 * Storekeeper module: receive incoming stock, issue outgoing stock, and
 * perform physical counts. Any adjustment requires a reference/permit number
 * as required by the spec ("based on official permits").
 */
class StockController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function movements(Request $request)
    {
        return StockMovement::query()
            ->with(['product', 'warehouse', 'creator'])
            ->when($request->query('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->query('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))
            ->orderByDesc('created_at')
            ->paginate(50);
    }

    public function receive(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->inventory->increase(
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity: $data['quantity'],
            type: StockMovement::PURCHASE,
            unitCost: $data['unit_cost'] ?? null,
            referenceType: 'manual_receipt',
            referenceNumber: $data['reference_number'],
            notes: $data['notes'] ?? null,
            actor: $request->user(),
        );

        return response()->json($movement, 201);
    }

    public function issue(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reference_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->inventory->decrease(
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity: $data['quantity'],
            type: StockMovement::ADJUSTMENT,
            referenceType: 'manual_issue',
            referenceNumber: $data['reference_number'],
            notes: $data['notes'] ?? null,
            actor: $request->user(),
        );

        return response()->json($movement, 201);
    }

    public function adjustToCount(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'reference_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->inventory->adjustToCountedQuantity(
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            countedQuantity: $data['counted_quantity'],
            referenceNumber: $data['reference_number'],
            notes: $data['notes'] ?? null,
            actor: $request->user(),
        );

        return response()->json($movement, 201);
    }
}
