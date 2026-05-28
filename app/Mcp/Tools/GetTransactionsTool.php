<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\LogsEgress;
use App\Models\Transaction;
use App\Services\TransactionSanitizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Get transactions')]
#[Description('Fetch a bounded, PII-sanitized window of transactions, optionally filtered by date range, account label and category. Use this to analyse spending habits over a period.')]
#[IsReadOnly]
class GetTransactionsTool extends Tool
{
    use LogsEgress;

    protected string $name = 'get_transactions';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'account' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $sanitizer = app(TransactionSanitizer::class);

        $transactions = Transaction::query()
            ->when($validated['period_start'] ?? null, fn ($query, $start) => $query->where('date', '>=', $start))
            ->when($validated['period_end'] ?? null, fn ($query, $end) => $query->where('date', '<=', $end))
            ->when($validated['account'] ?? null, fn ($query, $account) => $query->where('account', $account))
            ->when($validated['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($request->integer('limit') ?: 500)
            ->get()
            ->map(fn (Transaction $transaction): array => $sanitizer->sanitizeTransaction($transaction))
            ->all();

        return $this->logged('tool', $this->name(), [
            'count' => count($transactions),
            'transactions' => $transactions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'period_start' => $schema->string()->format('date')->description('Inclusive start date (YYYY-MM-DD).'),
            'period_end' => $schema->string()->format('date')->description('Inclusive end date (YYYY-MM-DD).'),
            'account' => $schema->string()->description('Filter to a single account label, e.g. "Checking".'),
            'category' => $schema->string()->description('Filter to a single category.'),
            'limit' => $schema->integer()->description('Max transactions to return (1-1000, default 500).'),
        ];
    }
}
