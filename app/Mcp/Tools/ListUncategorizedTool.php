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

#[Title('List uncategorized transactions')]
#[Description('Fetch transactions that have no category and are not locked, PII-sanitized. Use this to decide categories, then write them back with set_category.')]
#[IsReadOnly]
class ListUncategorizedTool extends Tool
{
    use LogsEgress;

    protected string $name = 'list_uncategorized';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'account' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $sanitizer = app(TransactionSanitizer::class);

        $transactions = Transaction::query()
            ->whereNull('category')
            ->where('category_locked', false)
            ->when($validated['account'] ?? null, fn ($query, $account) => $query->where('account', $account))
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
            'account' => $schema->string()->description('Filter to a single account label.'),
            'limit' => $schema->integer()->description('Max transactions to return (1-1000, default 500).'),
        ];
    }
}
