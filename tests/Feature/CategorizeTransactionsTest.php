<?php

declare(strict_types=1);

use App\Jobs\CategorizeTransactions;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\AiGatewayService;

it('categorizes uncategorized transactions via the gateway', function (): void {
    $t1 = Transaction::factory()->create(['category' => null, 'description' => 'Supermarket']);
    $t2 = Transaction::factory()->create(['category' => null, 'description' => 'Restaurant']);

    $mock = Mockery::mock(AiGatewayService::class);
    $mock->shouldReceive('categorize')
        ->once()
        ->andReturn([
            ['id' => $t1->id, 'category' => 'Groceries'],
            ['id' => $t2->id, 'category' => 'Dining'],
        ]);

    app()->instance(AiGatewayService::class, $mock);

    CategorizeTransactions::dispatchSync([$t1->id, $t2->id]);

    expect($t1->fresh()->category)->toBe('Groceries');
    expect($t2->fresh()->category)->toBe('Dining');
});

it('skips already categorized transactions', function (): void {
    $t1 = Transaction::factory()->create(['category' => 'Existing']);

    $mock = Mockery::mock(AiGatewayService::class);
    $mock->shouldNotReceive('categorize');

    app()->instance(AiGatewayService::class, $mock);

    CategorizeTransactions::dispatchSync([$t1->id]);

    expect($t1->fresh()->category)->toBe('Existing');
});

it('uses default categories when none exist in database', function (): void {
    $t = Transaction::factory()->create(['category' => null]);

    $mock = Mockery::mock(AiGatewayService::class);
    $mock->shouldReceive('categorize')
        ->once()
        ->withArgs(fn ($transactions, $categories): bool => in_array('Groceries', $categories, true)
            && in_array('Dining', $categories, true))
        ->andReturn([
            ['id' => $t->id, 'category' => 'Groceries'],
        ]);

    app()->instance(AiGatewayService::class, $mock);

    CategorizeTransactions::dispatchSync([$t->id]);
});

it('uses database categories when they exist', function (): void {
    Category::factory()->create(['name' => 'CustomCat']);
    $t = Transaction::factory()->create(['category' => null]);

    $mock = Mockery::mock(AiGatewayService::class);
    $mock->shouldReceive('categorize')
        ->once()
        ->withArgs(fn ($transactions, $categories): bool => $categories === ['CustomCat'])
        ->andReturn([
            ['id' => $t->id, 'category' => 'CustomCat'],
        ]);

    app()->instance(AiGatewayService::class, $mock);

    CategorizeTransactions::dispatchSync([$t->id]);
});
