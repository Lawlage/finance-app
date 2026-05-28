<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\AnalysisRun;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Record spending analysis')]
#[Description('Save a spending analysis for a period so it appears in the app on the Analysis page. Provide your recommendations as Markdown.')]
class RecordAnalysisTool extends Tool
{
    protected string $name = 'record_analysis';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'recommendations' => ['required', 'string'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        $run = AnalysisRun::create([
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'prompt_used' => null,
            'llm_response' => $validated['recommendations'],
            'model' => $validated['model'] ?? 'claude (mcp)',
        ]);

        return Response::text("Saved analysis #{$run->id}. View it on the Analysis page.");
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'period_start' => $schema->string()->format('date')->description('Inclusive start of the analysed period (YYYY-MM-DD).')->required(),
            'period_end' => $schema->string()->format('date')->description('Inclusive end of the analysed period (YYYY-MM-DD).')->required(),
            'recommendations' => $schema->string()->description('The analysis and recommendations, in Markdown.')->required(),
            'model' => $schema->string()->description('Optional model label, e.g. "claude-opus-4-7".'),
        ];
    }
}
