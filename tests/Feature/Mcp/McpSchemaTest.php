<?php

declare(strict_types=1);

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

it('builds a valid input schema for every tool', function (string $tool): void {
    $array = app($tool)->toArray();

    expect($array)->toHaveKey('name')
        ->and($array)->toHaveKey('inputSchema')
        ->and($array['inputSchema'])->toHaveKey('properties');
})->with([
    GetTransactionsTool::class,
    ListUncategorizedTool::class,
    SetCategoryTool::class,
    BulkSetCategoryTool::class,
    RecordAnalysisTool::class,
]);

it('exposes a uri and json mime for every resource', function (string $resource): void {
    $instance = app($resource);

    expect($instance->uri())->toStartWith('finance://')
        ->and($instance->mimeType())->toBe('application/json');
})->with([
    TransactionsResource::class,
    SpendingSummaryResource::class,
    CategoriesResource::class,
    CategoryRulesResource::class,
    AnalysisHistoryResource::class,
]);

it('declares prompt arguments', function (): void {
    $array = (new AnalyzeSpendingPrompt)->toArray();

    expect($array)->toHaveKey('arguments')
        ->and($array['arguments'])->toHaveCount(2);
});
