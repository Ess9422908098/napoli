<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        return Warehouse::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in([Warehouse::RAW_MATERIALS, Warehouse::WIP, Warehouse::FINISHED_GOODS])],
        ]);

        $warehouse = Warehouse::create($data);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'created',
            'subject_type' => Warehouse::class,
            'subject_id' => $warehouse->id,
            'subject_name' => $warehouse->name,
            'description' => 'تم إضافة مخزن جديد',
        ]);

        return response()->json($warehouse, 201);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'name' => ['sometimes', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update($data);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => Warehouse::class,
            'subject_id' => $warehouse->id,
            'subject_name' => $warehouse->name,
            'description' => 'تم تعديل المخزن',
        ]);

        return $warehouse;
    }

    /** Read-only stock snapshot, reused by Sales (read-only) and Production (raw materials only) views. */
    public function stockSnapshot(Request $request)
    {
        return ProductStock::query()
            ->with(['product', 'warehouse'])
            ->when($request->query('warehouse_type'), fn ($q, $type) => $q->whereHas('warehouse', fn ($w) => $w->where('type', $type)))
            ->when($request->query('product_type'), fn ($q, $type) => $q->whereHas('product', fn ($p) => $p->where('type', $type)))
            ->get()
            ->map(fn (ProductStock $stock) => [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'sku' => $stock->product->sku,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'quantity' => (float) $stock->quantity,
                'reserved_quantity' => (float) $stock->reserved_quantity,
                'available_quantity' => (float) $stock->quantity - (float) $stock->reserved_quantity,
            ]);
    }
}
