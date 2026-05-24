<?php

declare(strict_types=1);

use App\Jobs\RunSpendingAnalysis;
use App\Models\AnalysisRun;
use App\Models\Transaction;
use App\Services\AiGatewayService;

it('aggregates spending and creates an analysis run', function (): void {
    Transaction::factory()->create([
        'date' => '2026-01-15',
        'amount' => -50.00,
        'category' => 'Groceries',
    ]);

    Transaction::factory()->create([
        'date' => '2026-01-20',
        'amount' => -30.00,
        'category' => 'Groceries',
    ]);

    Transaction::factory()->create([
        'date' => '2026-01-10',
        'amount' => -25.00,
        'category' => 'Dining',
    ]);

    $mock = Mockery::mock(AiGatewayService::class);
    $mock->shouldReceive('analyze')
        ->once()
        ->withArgs(fn ($start, $end, $summary): bool => $start === '2026-01-01'
            && $end === '2026-01-31'
            && count($summary) === 2)
        ->andReturn([
            'recommendations' => 'Reduce grocery spending.',
            'model' => 'llama3.3:70b',
        ]);

    app()->instance(AiGatewayService::class, $mock);

    RunSpendingAnalysis::dispatchSync('2026-01-01', '2026-01-31');

    expect(AnalysisRun::count())->toBe(1);

    $run = AnalysisRun::first();
    expect($run->llm_response)->toBe('Reduce grocery spending.');
    expect($run->model)->toBe('llama3.3:70b');
});
