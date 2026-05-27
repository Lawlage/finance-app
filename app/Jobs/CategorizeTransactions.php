<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CategoryRule;
use App\Models\JobStatus;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Applies the user's local keyword rules to categorize transactions. Anything
 * the rules don't match is left uncategorized for the user's Claude client to
 * categorize over MCP (set_category / bulk_set_category). No AI runs here.
 */
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

    public function handle(): void
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

        foreach ($transactions as $transaction) {
            foreach ($rules as $rule) {
                if (str_contains(strtolower((string) $transaction->description), strtolower($rule->pattern))) {
                    $transaction->update(['category' => $rule->category]);
                    $ruleMatched++;
                    break;
                }
            }
        }

        $remaining = $transactions->count() - $ruleMatched;
        Log::info('Applied category rules', ['matched' => $ruleMatched, 'remaining' => $remaining]);

        $message = $remaining > 0
            ? "Categorized {$ruleMatched} via rules — {$remaining} left for Claude (MCP)"
            : "Categorized {$ruleMatched} transactions via rules";

        $status?->markCompleted($message);
    }

    public function failed(?\Throwable $exception): void
    {
        if ($this->jobStatusId) {
            $status = JobStatus::find($this->jobStatusId);
            $status?->markFailed('Categorization failed: '.($exception?->getMessage() ?? 'Unknown error'));
        }
    }
}
