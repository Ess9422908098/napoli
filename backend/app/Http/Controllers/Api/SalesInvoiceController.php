<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Services\SalesService;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    public function __construct(private readonly SalesService $salesService)
    {
    }

    public function index(Request $request)
    {
        return SalesInvoice::query()
            ->with(['items.product', 'items.warehouse', 'customer', 'creator', 'approver'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('approval_status'), fn ($q, $approvalStatus) => $q->where('approval_status', $approvalStatus))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    public function show(SalesInvoice $salesInvoice)
    {
        return $salesInvoice->load(['items.product', 'items.warehouse', 'customer', 'creator', 'fulfiller', 'approver']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = $this->salesService->createInvoice(
            items: $data['items'],
            customerId: $data['customer_id'] ?? null,
            salesUser: $request->user(),
            notes: $data['notes'] ?? null,
        );

        return response()->json($invoice, 201);
    }

    /** Storekeeper action: prepares and ships the reserved items. */
    public function approve(Request $request, SalesInvoice $salesInvoice)
    {
        $invoice = $this->salesService->approveInvoice($salesInvoice, $request->user());

        return response()->json($invoice);
    }

    public function fulfill(Request $request, SalesInvoice $salesInvoice)
    {
        $invoice = $this->salesService->fulfillInvoice($salesInvoice, $request->user());

        return response()->json($invoice);
    }

    public function cancel(SalesInvoice $salesInvoice)
    {
        return response()->json($this->salesService->cancelInvoice($salesInvoice));
    }
}
