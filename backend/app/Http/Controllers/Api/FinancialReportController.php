<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function monthly(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $query = JournalEntry::query()->with('lines.account');
        if ($from) {
            $query->whereDate('entry_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('entry_date', '<=', $to);
        }

        $entries = $query->orderBy('entry_date')->get();
        $monthly = [];

        foreach ($entries as $entry) {
            $month = $entry->entry_date?->format('Y-m') ?? 'unknown';
            $monthly[$month] = $monthly[$month] ?? ['month' => $month, 'debit' => 0, 'credit' => 0, 'entries' => 0];
            $monthly[$month]['debit'] += (float) $entry->lines->sum('debit');
            $monthly[$month]['credit'] += (float) $entry->lines->sum('credit');
            $monthly[$month]['entries'] += 1;
        }

        return response()->json(array_values($monthly));
    }

    public function summary(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Account::query()->withSum(['journalLines as total_debit' => fn ($q) => $q->whereHas('journalEntry', function ($entryQuery) use ($from, $to) {
            if ($from) {
                $entryQuery->whereDate('entry_date', '>=', $from);
            }
            if ($to) {
                $entryQuery->whereDate('entry_date', '<=', $to);
            }
        })], 'debit')
            ->withSum(['journalLines as total_credit' => fn ($q) => $q->whereHas('journalEntry', function ($entryQuery) use ($from, $to) {
                if ($from) {
                    $entryQuery->whereDate('entry_date', '>=', $from);
                }
                if ($to) {
                    $entryQuery->whereDate('entry_date', '<=', $to);
                }
            })], 'credit');

        $accounts = $query->get();
        $revenue = (float) $accounts->where('type', Account::REVENUE)->sum('total_credit');
        $expenses = (float) $accounts->where('type', Account::EXPENSE)->sum('total_debit');
        $net = $revenue - $expenses;

        return response()->json([
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $net,
            'breakdown' => [
                'revenue_accounts' => $accounts->where('type', Account::REVENUE)->values(),
                'expense_accounts' => $accounts->where('type', Account::EXPENSE)->values(),
            ],
        ]);
    }

    public function alerts()
    {
        $pendingInvoices = \App\Models\SalesInvoice::query()->where('status', \App\Models\SalesInvoice::PENDING_FULFILLMENT)->count();
        $lowStock = \App\Models\ProductStock::query()->whereColumn('quantity', '<=', 'reserved_quantity')->count();
        $alerts = [];

        if ($pendingInvoices > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'فواتير معلقة',
                'message' => "يوجد {$pendingInvoices} فواتير معلقة تحتاج تجهيزًا.",
            ];
        }

        if ($lowStock > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'مخزون منخفض',
                'message' => "يوجد {$lowStock} عنصرًا في وضع مخزون منخفض أو متاح صفر.",
            ];
        }

        return response()->json($alerts);
    }
}
