<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AnalyzeSpendingPrompt;
use App\Mcp\Resources\AnalysisHistoryResource;
use App\Mcp\Resources\CategoriesResource;
use App\Mcp\Resources\CategoryRulesResource;
use App\Mcp\Resources\SpendingSummaryResource;
use App\Mcp\Resources\TransactionsResource;
use App\Mcp\Tools\BulkSetCategoryTool;
use App\Mcp\Tools\GetTransactionsTool;
use App\Mcp\Tools\ListUncategorizedTool;
use App\Mcp\Tools\RecordAnalysisTool;
use App\Mcp\Tools\SetCategoryTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Finance Analyzer')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Read PII-sanitized personal finance data and help analyse spending habits and categorize transactions.

All transaction text is sanitized before it reaches you: account numbers, cards, emails, phones and personal names are removed or replaced with stable tags (e.g. Person-2B) or user-defined labels. Never ask for raw bank data — it is intentionally unavailable.

Typical flow: read finance://spending-summary and finance://transactions (or call get_transactions), reason about the data, then write results back with set_category / bulk_set_category and record_analysis.
TEXT)]
class FinanceServer extends Server
{
    protected array $tools = [
        GetTransactionsTool::class,
        ListUncategorizedTool::class,
        SetCategoryTool::class,
        BulkSetCategoryTool::class,
        RecordAnalysisTool::class,
    ];

    protected array $resources = [
        TransactionsResource::class,
        SpendingSummaryResource::class,
        CategoriesResource::class,
        CategoryRulesResource::class,
        AnalysisHistoryResource::class,
    ];

    protected array $prompts = [
        AnalyzeSpendingPrompt::class,
    ];
}
