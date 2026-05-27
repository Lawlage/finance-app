<?php

declare(strict_types=1);

use App\Mcp\Servers\FinanceServer;
use App\Models\ReplacementRule;
use App\Models\Setting;

it('does not register the replacement map or settings as MCP primitives', function (): void {
    $defaults = (new ReflectionClass(FinanceServer::class))->getDefaultProperties();

    $names = [
        ...$defaults['tools'],
        ...$defaults['resources'],
        ...$defaults['prompts'],
    ];

    $joined = implode(' ', $names);

    expect($joined)->not->toContain(ReplacementRule::class)
        ->not->toContain(Setting::class)
        ->not->toContain('McpAccessLog');
});

it('requires authentication on the MCP web endpoint', function (): void {
    $this->postJson('/mcp/finance', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'ping',
    ])->assertUnauthorized();
});
