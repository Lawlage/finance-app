<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
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
