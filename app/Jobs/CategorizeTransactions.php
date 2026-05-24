<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\AiGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CategorizeTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<int>  $transactionIds
     */
    public function __construct(
        private readonly array $transactionIds,
    ) {}

    public function handle(AiGatewayService $gateway): void
    {
        $transactions = Transaction::whereIn('id', $this->transactionIds)
            ->whereNull('category')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        /** @var array<int, string> $categories */
        $categories = Category::pluck('name')->toArray();

        if ($categories === []) {
            $categories = [
                'Groceries', 'Dining', 'Transport', 'Utilities',
                'Income', 'Rent', 'Healthcare', 'Entertainment', 'Other',
            ];
        }

        /** @var array<int, array{id: int, description: string, amount: float}> $payload */
        $payload = $transactions->map(fn (Transaction $t): array => [
            'id' => $t->id,
            'description' => $t->description,
            'amount' => (float) $t->amount,
        ])->toArray();

        $results = $gateway->categorize($payload, $categories);

        foreach ($results as $result) {
            Transaction::where('id', $result['id'])
                ->update(['category' => $result['category']]);
        }

        Log::info('Categorized transactions', ['count' => count($results)]);
    }
}
