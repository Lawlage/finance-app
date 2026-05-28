<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Concerns\LogsEgress;
use App\Models\Transaction;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Title('Spending summary')]
#[Description('Aggregated spending with a time dimension: totals per category and monthly income-vs-expense trends. Aggregate only — contains no transaction descriptions or PII.')]
#[Uri('finance://spending-summary')]
#[MimeType('application/json')]
class SpendingSummaryResource extends Resource
{
    use LogsEgress;

    protected string $name = 'spending-summary';

    public function handle(Request $request): Response
    {
        $byCategory = Transaction::query()
            ->excludingLoanAccounts()
            ->where('amount', '<', 0)
            ->whereNotNull('category')
            ->where('category', '!=', Transaction::TRANSFER_CATEGORY)
            ->toBase()
            ->selectRaw('category, SUM(ABS(amount)) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $row): array => [
                'category' => $row->category,
                'total' => (float) $row->total,
            ])
            ->all();

        $monthly = Transaction::query()
            ->excludingLoanAccounts()
            ->excludingTransfers()
            ->toBase()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month")
            ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income')
            ->selectRaw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses')
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn (object $row): array => [
                'month' => $row->month,
                'income' => (float) $row->income,
                'expenses' => (float) $row->expenses,
            ])
            ->all();

        return $this->logged('resource', $this->uri(), [
            'by_category' => $byCategory,
            'monthly' => $monthly,
        ]);
    }
}
