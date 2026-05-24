<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\JobStatus;
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
        private readonly ?int $jobStatusId = null,
    ) {}

    public function handle(AiGatewayService $gateway): void
    {
        $status = $this->jobStatusId ? JobStatus::find($this->jobStatusId) : null;

        $transactions = Transaction::whereIn('id', $this->transactionIds)
            ->whereNull('category')
            ->where('category_locked', false)
            ->get();

        if ($transactions->isEmpty()) {
            $status?->markCompleted('No uncategorized transactions to process');

            return;
        }

        $rules = CategoryRule::all();
        $ruleMatched = 0;

        if ($rules->isNotEmpty()) {
            foreach ($transactions as $transaction) {
                foreach ($rules as $rule) {
                    if (str_contains(strtolower($transaction->description), strtolower($rule->pattern))) {
                        $transaction->update(['category' => $rule->category]);
                        $ruleMatched++;
                        break;
                    }
                }
            }

            if ($ruleMatched > 0) {
                Log::info('Applied category rules', ['count' => $ruleMatched]);
            }

            $transactions = Transaction::whereIn('id', $this->transactionIds)
                ->whereNull('category')
                ->where('category_locked', false)
                ->get();

            if ($transactions->isEmpty()) {
                $status?->markCompleted("Categorized {$ruleMatched} transactions via rules");

                return;
            }
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

        $aiCount = count($results);
        $total = $ruleMatched + $aiCount;
        Log::info('Categorized transactions', ['rules' => $ruleMatched, 'ai' => $aiCount]);
        $status?->markCompleted("Categorized {$total} transactions ({$ruleMatched} rules, {$aiCount} AI)");
    }

    public function failed(?\Throwable $exception): void
    {
        if ($this->jobStatusId) {
            $status = JobStatus::find($this->jobStatusId);
            $status?->markFailed('Categorization failed: '.($exception?->getMessage() ?? 'Unknown error'));
        }
    }
}
