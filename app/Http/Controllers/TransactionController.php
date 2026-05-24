<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Jobs\CategorizeTransactions;
use App\Models\JobStatus;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $status = JobStatus::start('categorize', 'Categorizing '.count($uncategorizedIds).' transactions...');
        CategorizeTransactions::dispatch($uncategorizedIds, $status->id);

        return redirect()->back()->with('success', 'Categorization job dispatched.');
    }

    public function updateCategory(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'create_rule' => ['boolean'],
        ]);

        $transaction->update([
            'category' => $validated['category'],
            'category_locked' => true,
        ]);

        return redirect()->back()->with('success', 'Category updated.');
    }
}
