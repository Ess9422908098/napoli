<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class AccountingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $entriesQuery = JournalEntry::query()->with('lines.account');
        if ($from) {
            $entriesQuery->whereDate('entry_date', '>=', $from);
        }
        if ($to) {
            $entriesQuery->whereDate('entry_date', '<=', $to);
        }

        $entries = $entriesQuery->orderByDesc('entry_date')->take(5)->get();
        $pendingInvoices = SalesInvoice::query()->where('status', SalesInvoice::PENDING_FULFILLMENT)->count();
        $fulfilledInvoices = SalesInvoice::query()->where('status', SalesInvoice::FULFILLED)->count();

        $totalDebit = (float) $entries->sum(fn ($entry) => $entry->lines->sum('debit'));
        $totalCredit = (float) $entries->sum(fn ($entry) => $entry->lines->sum('credit'));

        return response()->json([
            'summary' => [
                'pending_invoices' => $pendingInvoices,
                'fulfilled_invoices' => $fulfilledInvoices,
                'recent_entries' => $entries->count(),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ],
            'entries' => $entries->map(fn ($entry) => [
                'id' => $entry->id,
                'entry_number' => $entry->entry_number,
                'description' => $entry->description,
                'entry_date' => $entry->entry_date?->toDateString(),
                'total_debit' => (float) $entry->lines->sum('debit'),
                'total_credit' => (float) $entry->lines->sum('credit'),
            ]),
        ]);
    }
}
