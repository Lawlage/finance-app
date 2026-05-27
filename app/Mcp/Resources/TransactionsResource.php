<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Concerns\LogsEgress;
use App\Models\Transaction;
use App\Services\TransactionSanitizer;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Title('Recent transactions')]
#[Description('The most recent transactions, PII-sanitized. Each item has id, date, amount, account label, category and a sanitized description. Raw bank text is never included.')]
#[Uri('finance://transactions')]
#[MimeType('application/json')]
class TransactionsResource extends Resource
{
    use LogsEgress;

    protected string $name = 'transactions';

    public function handle(Request $request): Response
    {
        $sanitizer = app(TransactionSanitizer::class);

        $transactions = Transaction::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Transaction $transaction): array => $sanitizer->sanitizeTransaction($transaction))
            ->all();

        return $this->logged('resource', $this->uri(), [
            'count' => count($transactions),
            'transactions' => $transactions,
        ]);
    }
}
