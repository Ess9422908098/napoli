<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class ReceivablesController extends Controller
{
    public function customers(Request $request)
    {
        $customers = Customer::query()->with(['invoices' => function ($q) {
            $q->where('status', '!=', SalesInvoice::CANCELLED);
        }])->get();

        $rows = $customers->map(function ($customer) {
            $total = (float) $customer->invoices->sum('total_amount');
            $paid = 0;
            $balance = $total - $paid;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'total_invoiced' => $total,
                'balance' => $balance,
            ];
        });

        return response()->json($rows);
    }

    public function suppliers(Request $request)
    {
        $suppliers = Supplier::query()->with(['purchaseOrders' => function ($q) {
            $q->where('status', '!=', PurchaseOrder::CANCELLED);
        }])->get();

        $rows = $suppliers->map(function ($supplier) {
            $total = (float) $supplier->purchaseOrders->sum('total_amount');
            $paid = (float) $supplier->purchaseOrders->sum('paid_amount');
            $balance = $total - $paid;

            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'total_purchased' => $total,
                'paid_amount' => $paid,
                'balance' => $balance,
            ];
        });

        return response()->json($rows);
    }

    public function customerStatement(Customer $customer)
    {
        $invoices = $customer->invoices()->where('status', '!=', SalesInvoice::CANCELLED)->orderByDesc('created_at')->get();

        return response()->json([
            'customer' => $customer,
            'invoices' => $invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'created_at' => $invoice->created_at?->toDateString(),
            ]),
        ]);
    }

    public function supplierStatement(Supplier $supplier)
    {
        $orders = $supplier->purchaseOrders()->where('status', '!=', PurchaseOrder::CANCELLED)->orderByDesc('created_at')->get();

        return response()->json([
            'supplier' => $supplier,
            'orders' => $orders->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'payment_status' => $order->payment_status,
                'remaining_amount' => (float) $order->total_amount - (float) $order->paid_amount,
                'created_at' => $order->created_at?->toDateString(),
            ]),
        ]);
    }
}
