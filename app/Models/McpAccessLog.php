<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpAccessLog extends Model
{
    protected $fillable = [
        'primitive',
        'name',
        'payload',
    ];

    /**
     * Record exactly what left the box through an MCP primitive, so the user
     * can audit ("what did Claude see?") that only sanitized data was sent.
     */
    public static function record(string $primitive, string $name, string $payload): void
    {
        self::query()->create([
            'primitive' => $primitive,
            'name' => $name,
            'payload' => $payload,
        ]);
    }
}
