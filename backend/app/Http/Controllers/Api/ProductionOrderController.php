<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    public function __construct(private readonly ProductionService $productionService)
    {
    }

    public function index(Request $request)
    {
        return ProductionOrder::query()
            ->with(['finishedProduct', 'materials.rawMaterial', 'rawWarehouse', 'finishedWarehouse'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    public function show(ProductionOrder $productionOrder)
    {
        return $productionOrder->load(['finishedProduct', 'materials.rawMaterial', 'rawWarehouse', 'finishedWarehouse']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'finished_product_id' => ['required', 'exists:products,id'],
            'quantity_to_produce' => ['required', 'numeric', 'min:0.0001'],
            'raw_warehouse_id' => ['required', 'exists:warehouses,id'],
            'finished_warehouse_id' => ['required', 'exists:warehouses,id'],
            'wip_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->productionService->createOrder(
            finishedProductId: $data['finished_product_id'],
            quantityToProduce: $data['quantity_to_produce'],
            rawWarehouseId: $data['raw_warehouse_id'],
            finishedWarehouseId: $data['finished_warehouse_id'],
            wipWarehouseId: $data['wip_warehouse_id'] ?? null,
            productionUser: $request->user(),
            notes: $data['notes'] ?? null,
        );

        return response()->json($order, 201);
    }

    public function start(ProductionOrder $productionOrder)
    {
        return response()->json($this->productionService->startOrder($productionOrder));
    }

    /** Completing the order auto-consumes raw materials and produces finished goods. */
    public function complete(Request $request, ProductionOrder $productionOrder)
    {
        $order = $this->productionService->completeOrder($productionOrder, $request->user());

        return response()->json($order);
    }
}
