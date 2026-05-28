<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Title('Set transaction category')]
#[Description('Assign a category to a single transaction. The transaction is then locked so automatic categorization will not overwrite it.')]
#[IsIdempotent]
class SetCategoryTool extends Tool
{
    protected string $name = 'set_category';

    public function handle(Request $request): Response
    {
        $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'category' => ['required', 'string', 'max:255'],
        ]);

        $category = $request->string('category')->toString();

        // Promote any new category to a first-class, managed category.
        Category::firstOrCreate(['name' => $category]);

        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($request->integer('transaction_id'));
        $transaction->update([
            'category' => $category,
            'category_locked' => true,
        ]);

        return Response::text("Transaction {$transaction->id} categorized as \"{$category}\".");
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction_id' => $schema->integer()->description('ID of the transaction to categorize.')->required(),
            'category' => $schema->string()->description('Category name to assign.')->required(),
        ];
    }
}
