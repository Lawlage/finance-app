<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnalysisRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisRun extends Model
{
    /** @use HasFactory<AnalysisRunFactory> */
    use HasFactory;

    protected $fillable = [
        'period_start',
        'period_end',
        'prompt_used',
        'llm_response',
        'model',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }
}
