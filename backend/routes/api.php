<?php

use App\Http\Controllers\Api\AccountingDashboardController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AutomationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductionOrderController;
use App\Http\Controllers\Api\ReceivablesController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SalesInvoiceController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Role/Permission based access control
|--------------------------------------------------------------------------
|
| Every route (except /login) requires a valid Sanctum token. On top of
| that, each route/group is protected by a fine-grained "permission:"
| middleware so that a Sales user, for example, can never call a
| Storekeeper/Production/Accountant endpoint, and vice versa. Admin bypasses
| every permission/role check (see EnsureUserHasRole/EnsureUserHasPermission).
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/operations', [OperationsController::class, 'index']);
    Route::get('/accounting/dashboard', [AccountingDashboardController::class, 'index']);
    Route::get('/automation/health', [AutomationController::class, 'health']);
    Route::get('/financial-reports/monthly', [FinancialReportController::class, 'monthly']);
    Route::get('/financial-reports/summary', [FinancialReportController::class, 'summary']);
    Route::get('/financial-reports/alerts', [FinancialReportController::class, 'alerts']);
    Route::get('/receivables/customers', [ReceivablesController::class, 'customers']);
    Route::get('/receivables/suppliers', [ReceivablesController::class, 'suppliers']);
    Route::get('/receivables/customers/{customer}', [ReceivablesController::class, 'customerStatement']);
    Route::get('/receivables/suppliers/{supplier}', [ReceivablesController::class, 'supplierStatement']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // ---------------------------------------------------------------
    // Admin only: user & role management, master data (products, BOM,
    // warehouses, customers, suppliers, chart of accounts visibility).
    // ---------------------------------------------------------------
    Route::middleware('role:'.Role::ADMIN)->group(function () {
        Route::apiResource('users', UserController::class)->except(['show']);
        Route::get('roles', [UserController::class, 'roles']);
    });

    Route::middleware('permission:products.manage,stock.manage')->group(function () {
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::put('products/{product}/bom', [ProductController::class, 'updateBom']);
    });

    Route::middleware('permission:warehouses.manage')->group(function () {
        Route::post('warehouses', [WarehouseController::class, 'store']);
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
    });

    // Read-only master data available to any authenticated role that needs it.
    Route::get('products', [ProductController::class, 'index']);
    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('suppliers', [SupplierController::class, 'index']);

    Route::middleware('permission:customers.manage')->post('customers', [CustomerController::class, 'store']);
    Route::middleware('permission:suppliers.manage')->post('suppliers', [SupplierController::class, 'store']);

    // ---------------------------------------------------------------
    // Sales: create invoices + read-only stock visibility (no write access
    // to stock quantities).
    // ---------------------------------------------------------------
    Route::middleware('permission:invoices.create')->group(function () {
        Route::post('sales-invoices', [SalesInvoiceController::class, 'store']);
    });
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('sales-invoices', [SalesInvoiceController::class, 'index']);
        Route::get('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show']);
    });
    Route::middleware('permission:invoices.cancel')->post('sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel']);

    Route::middleware('permission:stock.view_readonly,stock.manage,production.view_raw_stock')
        ->get('stock/snapshot', [WarehouseController::class, 'stockSnapshot']);

    // ---------------------------------------------------------------
    // Storekeeper: receive/issue/adjust stock, physical counts, fulfill
    // sales invoices, receive purchase orders.
    // ---------------------------------------------------------------
    Route::middleware('permission:stock.manage')->group(function () {
        Route::get('stock/movements', [StockController::class, 'movements']);
        Route::post('stock/receive', [StockController::class, 'receive']);
        Route::post('stock/issue', [StockController::class, 'issue']);
        Route::post('stock/count', [StockController::class, 'adjustToCount']);
        Route::patch('sales-invoices/{salesInvoice}/fulfill', [SalesInvoiceController::class, 'fulfill']);
        Route::patch('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
    });

    // ---------------------------------------------------------------
    // Purchasing (admin/storekeeper): create purchase orders.
    // ---------------------------------------------------------------
    Route::middleware('permission:purchases.manage')->group(function () {
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
    });

    // ---------------------------------------------------------------
    // Production: manage production orders (consumes raw materials,
    // produces finished goods automatically on completion).
    // ---------------------------------------------------------------
    Route::middleware('permission:production.manage')->group(function () {
        Route::get('production-orders', [ProductionOrderController::class, 'index']);
        Route::get('production-orders/{productionOrder}', [ProductionOrderController::class, 'show']);
        Route::post('production-orders', [ProductionOrderController::class, 'store']);
        Route::patch('production-orders/{productionOrder}/start', [ProductionOrderController::class, 'start']);
        Route::patch('production-orders/{productionOrder}/complete', [ProductionOrderController::class, 'complete']);
    });

    // ---------------------------------------------------------------
    // Accountant: strictly read-only financial views, auto-generated only.
    // ---------------------------------------------------------------
    Route::middleware('permission:accounting.view')->group(function () {
        Route::get('journal-entries', [JournalEntryController::class, 'index']);
        Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);
        Route::get('accounts', [JournalEntryController::class, 'accounts']);
        Route::get('accounts/trial-balance', [JournalEntryController::class, 'trialBalance']);
        Route::get('payrolls', [PayrollController::class, 'index']);
    });

    Route::middleware('permission:accounting.audit')->group(function () {
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
    });

    Route::middleware('permission:payroll.manage')->group(function () {
        Route::post('payrolls', [PayrollController::class, 'store']);
        Route::patch('payrolls/{payroll}/post', [PayrollController::class, 'post']);
    });
});
