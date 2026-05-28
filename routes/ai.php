<?php

declare(strict_types=1);

use App\Mcp\Servers\FinanceServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Servers
|--------------------------------------------------------------------------
|
| The finance MCP server is local-only: it is reached over the LAN by the
| user's own Claude client (Claude Desktop / Code) using a Sanctum personal
| access token. There is deliberately no OAuth and no public exposure — the
| app stays on the LAN, like MySQL.
|
*/

Mcp::web('/mcp/finance', FinanceServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

// stdio transport for local development + `php artisan mcp:inspector finance`.
Mcp::local('finance', FinanceServer::class);
