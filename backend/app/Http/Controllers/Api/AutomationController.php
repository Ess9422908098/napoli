<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function health(Request $request)
    {
        $pendingInvoices = SalesInvoice::query()->where('status', SalesInvoice::PENDING_FULFILLMENT)->count();
        $lowStockItems = ProductStock::query()->whereColumn('quantity', '<=', 'reserved_quantity')->count();
        $lateProduction = ProductionOrder::query()->where('status', ProductionOrder::IN_PROGRESS)->count();
        $pendingPurchases = PurchaseOrder::query()->where('status', PurchaseOrder::DRAFT)->count();

        return response()->json([
            'automation' => [
                'journal_entries_enabled' => true,
                'inventory_updates_enabled' => true,
                'notifications_enabled' => true,
            ],
            'summary' => [
                'pending_invoices' => $pendingInvoices,
                'low_stock_items' => $lowStockItems,
                'late_production_orders' => $lateProduction,
                'pending_purchases' => $pendingPurchases,
            ],
        ]);
    }
}
