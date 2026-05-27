<?php

declare(strict_types=1);

use App\Mcp\Prompts\AnalyzeSpendingPrompt;
use App\Mcp\Resources\AnalysisHistoryResource;
use App\Mcp\Resources\CategoriesResource;
use App\Mcp\Resources\CategoryRulesResource;
use App\Mcp\Resources\SpendingSummaryResource;
use App\Mcp\Resources\TransactionsResource;
use App\Mcp\Servers\FinanceServer;
use App\Mcp\Tools\BulkSetCategoryTool;
use App\Mcp\Tools\GetTransactionsTool;
use App\Mcp\Tools\ListUncategorizedTool;
use App\Mcp\Tools\RecordAnalysisTool;
use App\Mcp\Tools\SetCategoryTool;
use App\Models\AnalysisRun;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\McpAccessLog;
use App\Models\ReplacementRule;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('set_category assigns and locks a transaction', function (): void {
    $transaction = Transaction::factory()->create(['category' => null, 'category_locked' => false]);

    FinanceServer::actingAs($this->user)
        ->tool(SetCategoryTool::class, [
            'transaction_id' => $transaction->id,
            'category' => 'Groceries',
        ])
        ->assertOk();

    expect($transaction->fresh()->category)->toBe('Groceries');
    expect($transaction->fresh()->category_locked)->toBeTrue();
});

it('set_category validates the transaction exists', function (): void {
    FinanceServer::actingAs($this->user)
        ->tool(SetCategoryTool::class, ['transaction_id' => 999999, 'category' => 'X'])
        ->assertHasErrors();
});

it('bulk_set_category updates many transactions', function (): void {
    $a = Transaction::factory()->create(['category' => null]);
    $b = Transaction::factory()->create(['category' => null]);

    FinanceServer::actingAs($this->user)
        ->tool(BulkSetCategoryTool::class, [
            'assignments' => [
                ['transaction_id' => $a->id, 'category' => 'Dining'],
                ['transaction_id' => $b->id, 'category' => 'Transport'],
            ],
        ])
        ->assertOk();

    expect($a->fresh()->category)->toBe('Dining');
    expect($b->fresh()->category)->toBe('Transport');
});

it('record_analysis persists an analysis run', function (): void {
    FinanceServer::actingAs($this->user)
        ->tool(RecordAnalysisTool::class, [
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'recommendations' => '## Spend less on dining',
        ])
        ->assertOk();

    expect(AnalysisRun::count())->toBe(1);
    expect(AnalysisRun::first()->model)->toBe('claude (mcp)');
});

it('get_transactions returns sanitized data and never raw_text', function (): void {
    ReplacementRule::create(['value' => '38-9009-0123456-00', 'label' => 'Joint Savings']);
    Transaction::factory()->create([
        'description' => 'Smith J R — RENT — 38-9009-0123456-00',
        'raw_text' => 'LEAKY 38-9009-0123456-00 secret@example.com',
        'account' => 'Checking',
    ]);

    FinanceServer::actingAs($this->user)
        ->tool(GetTransactionsTool::class, ['limit' => 10])
        ->assertOk()
        ->assertSee('Joint Savings')
        ->assertDontSee('LEAKY')
        ->assertDontSee('secret@example.com')
        ->assertDontSee('raw_text');
});

it('list_uncategorized only returns uncategorized, unlocked transactions', function (): void {
    Transaction::factory()->create(['category' => null, 'category_locked' => false, 'description' => 'Mystery payee']);
    Transaction::factory()->create(['category' => 'Groceries', 'description' => 'Known']);

    FinanceServer::actingAs($this->user)
        ->tool(ListUncategorizedTool::class, [])
        ->assertOk()
        ->assertSee('Mystery payee')
        ->assertDontSee('Known');
});

it('logs MCP egress to the audit trail', function (): void {
    Transaction::factory()->create();

    FinanceServer::actingAs($this->user)
        ->resource(TransactionsResource::class)
        ->assertOk();

    expect(McpAccessLog::where('primitive', 'resource')->count())->toBeGreaterThan(0);
});

it('spending-summary exposes category and monthly aggregates without descriptions', function (): void {
    Transaction::factory()->create([
        'description' => 'SECRET PAYEE',
        'amount' => -100,
        'category' => 'Groceries',
        'date' => '2026-03-15',
    ]);

    FinanceServer::actingAs($this->user)
        ->resource(SpendingSummaryResource::class)
        ->assertOk()
        ->assertSee('by_category')
        ->assertSee('monthly')
        ->assertDontSee('SECRET PAYEE');
});

it('spending-summary excludes transfers', function (): void {
    Transaction::factory()->create(['amount' => -200, 'category' => 'Groceries', 'date' => '2026-03-10']);
    Transaction::factory()->create(['amount' => -500, 'category' => 'Transfer', 'date' => '2026-03-10']);
    Transaction::factory()->create(['amount' => 500, 'category' => 'Transfer', 'date' => '2026-03-10']);

    FinanceServer::actingAs($this->user)
        ->resource(SpendingSummaryResource::class)
        ->assertOk()
        ->assertSee('Groceries')
        ->assertDontSee('Transfer');
});

it('categories resource lists category names', function (): void {
    Category::create(['name' => 'Groceries']);

    FinanceServer::actingAs($this->user)
        ->resource(CategoriesResource::class)
        ->assertOk()
        ->assertSee('Groceries');
});

it('category-rules resource lists rules', function (): void {
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'countdown']);

    FinanceServer::actingAs($this->user)
        ->resource(CategoryRulesResource::class)
        ->assertOk()
        ->assertSee('countdown');
});

it('analysis-history resource lists past analyses', function (): void {
    AnalysisRun::factory()->create(['llm_response' => 'Spend less on dining']);

    FinanceServer::actingAs($this->user)
        ->resource(AnalysisHistoryResource::class)
        ->assertOk()
        ->assertSee('Spend less on dining');
});

it('analyze-spending prompt guides the client for a period', function (): void {
    FinanceServer::actingAs($this->user)
        ->prompt(AnalyzeSpendingPrompt::class, [
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ])
        ->assertOk()
        ->assertSee('2026-01-01 to 2026-01-31')
        ->assertSee('record_analysis');
});

it('analyze-spending prompt falls back to all data without a period', function (): void {
    FinanceServer::actingAs($this->user)
        ->prompt(AnalyzeSpendingPrompt::class, [])
        ->assertOk()
        ->assertSee('all available data');
});
