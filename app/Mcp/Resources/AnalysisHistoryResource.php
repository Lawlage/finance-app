<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Concerns\LogsEgress;
use App\Models\AnalysisRun;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Title('Analysis history')]
#[Description('Previously recorded spending analyses, most recent first. Useful for comparing against earlier findings.')]
#[Uri('finance://analyses')]
#[MimeType('application/json')]
class AnalysisHistoryResource extends Resource
{
    use LogsEgress;

    protected string $name = 'analyses';

    public function handle(Request $request): Response
    {
        $analyses = [];

        foreach (AnalysisRun::query()->orderByDesc('created_at')->limit(50)->get() as $run) {
            $analyses[] = [
                'id' => (int) $run->id,
                'period_start' => Carbon::parse($run->period_start)->format('Y-m-d'),
                'period_end' => Carbon::parse($run->period_end)->format('Y-m-d'),
                'recommendations' => $run->llm_response,
                'model' => $run->model,
                'created_at' => Carbon::parse($run->created_at)->toIso8601String(),
            ];
        }

        return $this->logged('resource', $this->uri(), [
            'analyses' => $analyses,
        ]);
    }
}
