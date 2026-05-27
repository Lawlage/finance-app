<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Concerns\LogsEgress;
use App\Models\Category;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Title('Categories')]
#[Description('The list of spending categories available for assigning to transactions.')]
#[Uri('finance://categories')]
#[MimeType('application/json')]
class CategoriesResource extends Resource
{
    use LogsEgress;

    protected string $name = 'categories';

    public function handle(Request $request): Response
    {
        $categories = Category::query()->orderBy('name')->pluck('name')->all();

        return $this->logged('resource', $this->uri(), [
            'categories' => $categories,
        ]);
    }
}
