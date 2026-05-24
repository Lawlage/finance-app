<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AnalysisRun;
use App\Models\Transaction;
use App\Services\AiGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSpendingAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $periodStart,
        private readonly string $periodEnd,
    ) {}

    public function handle(AiGatewayService $gateway): void
    {
        /** @var array<int, array{category: string, total: float}> $summary */
        $summary = Transaction::whereBetween('date', [$this->periodStart, $this->periodEnd])
            ->whereNotNull('category')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(function (Transaction $row): array {
                /** @var string $total */
                $total = $row->getAttribute('total');

                return [
                    'category' => (string) $row->category,
                    'total' => floatval($total),
                ];
            })
            ->toArray();

        $promptUsed = sprintf(
            'Analyze spending from %s to %s across %d categories',
            $this->periodStart,
            $this->periodEnd,
            count($summary),
        );

        $result = $gateway->analyze($this->periodStart, $this->periodEnd, $summary);

        AnalysisRun::create([
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'prompt_used' => $promptUsed,
            'llm_response' => $result['recommendations'],
            'model' => $result['model'],
        ]);

        Log::info('Spending analysis completed', [
            'period' => "{$this->periodStart} to {$this->periodEnd}",
            'model' => $result['model'],
        ]);
    }
}
