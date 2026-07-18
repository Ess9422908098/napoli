<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionOrder::query()->with(['finishedProduct', 'rawWarehouse', 'wipWarehouse', 'finishedWarehouse', 'requester']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(10);

        return response()->json($orders);
    }
}
