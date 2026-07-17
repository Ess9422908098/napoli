<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseService $purchaseService)
    {
    }

    public function index(Request $request)
    {
        return PurchaseOrder::query()
            ->with(['items.product', 'supplier', 'warehouse'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $order = $this->purchaseService->createOrder(
            supplierId: $data['supplier_id'],
            warehouseId: $data['warehouse_id'],
            items: $data['items'],
            actor: $request->user(),
        );

        return response()->json($order, 201);
    }

    /** Storekeeper receives the goods physically: stock increases + payable journal entry. */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        return response()->json($this->purchaseService->receiveOrder($purchaseOrder, $request->user()));
    }
}
