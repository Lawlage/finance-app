<?php

declare(strict_types=1);

use App\Jobs\CategorizeTransactions;
use App\Models\CategoryRule;
use App\Models\JobStatus;
use App\Models\Transaction;

it('categorizes transactions matching a keyword rule', function (): void {
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'supermarket']);
    $t1 = Transaction::factory()->create(['category' => null, 'description' => 'COUNTDOWN SUPERMARKET']);
    $t2 = Transaction::factory()->create(['category' => null, 'description' => 'Unknown payee']);

    CategorizeTransactions::dispatchSync([$t1->id, $t2->id]);

    expect($t1->fresh()->category)->toBe('Groceries');
    expect($t2->fresh()->category)->toBeNull();
});

it('leaves unmatched transactions for the Claude client', function (): void {
    $status = JobStatus::start('categorize', 'Categorizing...');
    $t = Transaction::factory()->create(['category' => null, 'description' => 'Mystery']);

    CategorizeTransactions::dispatchSync([$t->id], $status->id);

    expect($t->fresh()->category)->toBeNull();
    expect($status->fresh()->status)->toBe('completed');
    expect($status->fresh()->message)->toContain('left for Claude');
});

it('skips already categorized or locked transactions', function (): void {
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'super']);
    $categorized = Transaction::factory()->create(['category' => 'Existing', 'description' => 'super market']);
    $locked = Transaction::factory()->create(['category' => null, 'category_locked' => true, 'description' => 'super market']);

    CategorizeTransactions::dispatchSync([$categorized->id, $locked->id]);

    expect($categorized->fresh()->category)->toBe('Existing');
    expect($locked->fresh()->category)->toBeNull();
});

it('completes cleanly when there are no rules', function (): void {
    $status = JobStatus::start('categorize', 'Categorizing...');
    $t = Transaction::factory()->create(['category' => null]);

    CategorizeTransactions::dispatchSync([$t->id], $status->id);

    expect($status->fresh()->status)->toBe('completed');
});
