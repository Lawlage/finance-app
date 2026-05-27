<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Title('Analyze my spending')]
#[Description('Guide Claude to analyse spending habits using the finance resources and record the result back into the app.')]
class AnalyzeSpendingPrompt extends Prompt
{
    protected string $name = 'analyze_spending';

    public function handle(Request $request): Response
    {
        $start = $request->string('period_start')->toString();
        $end = $request->string('period_end')->toString();

        $period = $start !== '' && $end !== ''
            ? "the period {$start} to {$end}"
            : 'all available data';

        $instructions = <<<PROMPT
        Analyse my personal spending habits for {$period}.

        1. Read the `finance://spending-summary` resource for category totals and monthly income-vs-expense trends.
        2. Use the `get_transactions` tool to drill into specific categories or periods as needed.
        3. Identify notable patterns: largest categories, month-over-month changes, recurring payments, and anything unusual.
        4. Give concrete, actionable recommendations.
        5. Save your findings with the `record_analysis` tool (period_start, period_end, recommendations as Markdown) so they appear in the app.

        Note: all data is PII-sanitized. Recurring counterparties may appear as stable tags like `Person-2B` or labels I have defined.
        PROMPT;

        return Response::text($instructions);
    }

    /**
     * @return array<int, Argument>
     */
    #[\Override]
    public function arguments(): array
    {
        return [
            new Argument('period_start', 'Optional inclusive start date (YYYY-MM-DD).'),
            new Argument('period_end', 'Optional inclusive end date (YYYY-MM-DD).'),
        ];
    }
}
