<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

/**
 * Accountant module: strictly read-only. Journal entries are only ever
 * created automatically by AccountingService from the Sales/Production/
 * Purchase/Payroll flows, never manually here.
 */
class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        return JournalEntry::query()
            ->with(['lines.account', 'creator'])
            ->when($request->query('reference_type'), fn ($q, $type) => $q->where('reference_type', $type))
            ->when($request->query('from'), fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
            ->when($request->query('to'), fn ($q, $date) => $q->whereDate('entry_date', '<=', $date))
            ->orderByDesc('entry_date')
            ->paginate(30);
    }

    public function show(JournalEntry $journalEntry)
    {
        return $journalEntry->load(['lines.account', 'creator']);
    }

    public function accounts()
    {
        return Account::orderBy('code')->get();
    }

    /** Simple trial balance: sum of debits/credits per account. */
    public function trialBalance()
    {
        return Account::query()
            ->withSum('journalLines as total_debit', 'debit')
            ->withSum('journalLines as total_credit', 'credit')
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'total_debit' => (float) ($account->total_debit ?? 0),
                'total_credit' => (float) ($account->total_credit ?? 0),
                'balance' => (float) ($account->total_debit ?? 0) - (float) ($account->total_credit ?? 0),
            ]);
    }
}
