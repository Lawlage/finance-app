<?php

declare(strict_types=1);

use App\Jobs\CategorizeTransactions;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('stores a new transaction', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/transactions', [
            'date' => '2026-05-10',
            'description' => 'Test transaction',
            'amount' => -42.50,
            'account' => 'Checking',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Transaction::count())->toBe(1);

    $transaction = Transaction::first();
    expect($transaction->description)->toBe('Test transaction');
    expect($transaction->raw_text)->toBe('Test transaction');
});

it('validates required transaction fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/transactions', [])
        ->assertSessionHasErrors(['date', 'description', 'amount', 'account']);
});

it('dispatches categorize job for uncategorized transactions', function (): void {
    Queue::fake([CategorizeTransactions::class]);
    $user = User::factory()->create();

    Transaction::factory()->count(3)->create(['category' => null]);

    $this->actingAs($user)
        ->post('/transactions/categorize')
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(CategorizeTransactions::class);
});

it('returns info when no uncategorized transactions exist', function (): void {
    Queue::fake([CategorizeTransactions::class]);
    $user = User::factory()->create();

    Transaction::factory()->count(2)->create(['category' => 'Groceries']);

    $this->actingAs($user)
        ->post('/transactions/categorize')
        ->assertRedirect()
        ->assertSessionHas('info');

    Queue::assertNotPushed(CategorizeTransactions::class);
});
