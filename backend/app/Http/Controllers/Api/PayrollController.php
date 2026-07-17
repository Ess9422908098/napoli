<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\AccountingService;
use Illuminate\Http\Request;

/** Accountant sees payroll entries and their automatic journal impact (read/create only, no manual journals). */
class PayrollController extends Controller
{
    public function __construct(private readonly AccountingService $accounting)
    {
    }

    public function index()
    {
        return Payroll::orderByDesc('created_at')->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_name' => ['required', 'string', 'max:150'],
            'month' => ['required', 'string', 'size:7'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $data['created_by'] = $request->user()->id;
        $payroll = Payroll::create($data);

        return response()->json($payroll, 201);
    }

    /** Posts the salary as an automatic financial entry, visible to the accountant instantly. */
    public function post(Request $request, Payroll $payroll)
    {
        if ($payroll->status === 'posted') {
            return response()->json(['message' => 'تم ترحيل هذا المرتب بالفعل.'], 422);
        }

        $this->accounting->postPayroll($payroll->id, $payroll->employee_name, (float) $payroll->amount, $request->user());

        $payroll->update(['status' => 'posted', 'posted_at' => now()]);

        return response()->json($payroll);
    }
}
