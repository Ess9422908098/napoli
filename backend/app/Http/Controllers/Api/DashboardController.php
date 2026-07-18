<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $pendingInvoices = SalesInvoice::query()->where('status', SalesInvoice::PENDING_FULFILLMENT)->count();
        $inProgressOrders = ProductionOrder::query()->where('status', ProductionOrder::IN_PROGRESS)->count();
        $recentMovements = StockMovement::query()
            ->with(['product', 'warehouse', 'creator'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $lowStockProducts = \App\Models\ProductStock::query()
            ->with('product')
            ->whereColumn('quantity', '<=', 'reserved_quantity')
            ->get();

        return response()->json([
            'summary' => [
                'pending_invoices' => $pendingInvoices,
                'production_orders_in_progress' => $inProgressOrders,
                'low_stock_products' => $lowStockProducts->count(),
            ],
            'recent_movements' => $recentMovements->map(fn ($movement) => [
                'id' => $movement->id,
                'product_name' => $movement->product?->name,
                'warehouse_name' => $movement->warehouse?->name,
                'type' => $movement->type,
                'quantity' => (float) $movement->quantity,
                'created_at' => $movement->created_at?->toISOString(),
                'creator_name' => $movement->creator?->name,
            ]),
            'low_stock_items' => $lowStockProducts->map(fn ($stock) => [
                'product_name' => $stock->product?->name,
                'warehouse_name' => $stock->warehouse?->name,
                'quantity' => (float) $stock->quantity,
                'reserved_quantity' => (float) $stock->reserved_quantity,
            ]),
        ]);
    }
}
