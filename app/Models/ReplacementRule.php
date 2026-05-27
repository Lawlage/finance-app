<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReplacementRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplacementRule extends Model
{
    /** @use HasFactory<ReplacementRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'value',
        'label',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            // Encrypted at rest — the literal account number / name is sensitive
            // and must never be readable in the database or exposed via MCP.
            'value' => 'encrypted',
        ];
    }
}
