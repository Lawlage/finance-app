<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Title('Bulk set transaction categories')]
#[Description('Assign categories to many transactions at once. Each assignment locks its transaction against automatic re-categorization.')]
#[IsIdempotent]
class BulkSetCategoryTool extends Tool
{
    protected string $name = 'bulk_set_category';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'assignments.*.category' => ['required', 'string', 'max:255'],
        ]);

        /** @var array<int, array{transaction_id: int|string, category: string}> $assignments */
        $assignments = $validated['assignments'];

        $updated = DB::transaction(function () use ($assignments): int {
            $count = 0;
            foreach ($assignments as $assignment) {
                $count += Transaction::where('id', (int) $assignment['transaction_id'])->update([
                    'category' => (string) $assignment['category'],
                    'category_locked' => true,
                ]);
            }

            return $count;
        });

        return Response::text("Categorized {$updated} transaction(s).");
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'assignments' => $schema->array()
                ->items($schema->object([
                    'transaction_id' => $schema->integer()->description('ID of the transaction.')->required(),
                    'category' => $schema->string()->description('Category to assign.')->required(),
                ]))
                ->description('List of {transaction_id, category} assignments.')
                ->required(),
        ];
    }
}
