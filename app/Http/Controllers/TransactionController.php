<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Jobs\CategorizeTransactions;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        Transaction::create([
            ...$request->validated(),
            'raw_text' => $request->input('description'),
        ]);

        return redirect()->back()->with('success', 'Transaction added successfully.');
    }

    public function categorize(): RedirectResponse
    {
        /** @var array<int, int> $uncategorizedIds */
        $uncategorizedIds = Transaction::whereNull('category')
            ->pluck('id')
            ->toArray();

        if ($uncategorizedIds === []) {
            return redirect()->back()->with('info', 'No uncategorized transactions found.');
        }

        CategorizeTransactions::dispatch($uncategorizedIds);

        return redirect()->back()->with('success', 'Categorization job dispatched.');
    }
}
