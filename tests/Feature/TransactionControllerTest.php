<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;

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

it('updates a transaction category and locks it', function (): void {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['category' => null, 'category_locked' => false]);

    $this->actingAs($user)
        ->patch("/transactions/{$transaction->id}/category", ['category' => 'Groceries'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($transaction->fresh()->category)->toBe('Groceries');
    expect($transaction->fresh()->category_locked)->toBeTrue();
});
