<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Concerns\LogsEgress;
use App\Models\CategoryRule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Title('Category rules')]
#[Description('User-defined keyword rules that auto-assign a category when a transaction description contains the pattern.')]
#[Uri('finance://category-rules')]
#[MimeType('application/json')]
class CategoryRulesResource extends Resource
{
    use LogsEgress;

    protected string $name = 'category-rules';

    public function handle(Request $request): Response
    {
        $rules = CategoryRule::query()
            ->orderBy('category')
            ->get(['category', 'pattern'])
            ->map(fn (CategoryRule $rule): array => [
                'category' => $rule->category,
                'pattern' => $rule->pattern,
            ])
            ->all();

        return $this->logged('resource', $this->uri(), [
            'rules' => $rules,
        ]);
    }
}
