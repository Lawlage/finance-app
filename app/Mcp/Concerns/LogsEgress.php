<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Models\McpAccessLog;
use Laravel\Mcp\Response;

trait LogsEgress
{
    /**
     * Encode a payload as JSON, record exactly what leaves the box to the
     * egress audit log, and return it as the MCP response. The logged string
     * is byte-for-byte what the client receives.
     *
     * @param  array<string, mixed>|list<mixed>  $payload
     */
    protected function logged(string $primitive, string $name, array $payload): Response
    {
        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );

        McpAccessLog::record($primitive, $name, $json);

        return Response::text($json);
    }
}
