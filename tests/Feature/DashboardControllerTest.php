<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;

it('redirects unauthenticated users from dashboard', function (): void {
    $this->get('/')->assertRedirect('/login');
});

it('shows the dashboard to authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard', false)
            ->has('spendingByCategory')
            ->has('monthlyTrends')
            ->has('recentTransactions')
            ->has('filters')
        );
});

it('returns spending by category for the current month', function (): void {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'date' => now()->startOfMonth(),
        'amount' => -50.00,
        'category' => 'Groceries',
    ]);

    Transaction::factory()->create([
        'date' => now()->startOfMonth(),
        'amount' => -30.00,
        'category' => 'Groceries',
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard', false)
            ->has('spendingByCategory', 1)
        );
});

it('returns recent transactions as paginated data', function (): void {
    $user = User::factory()->create();

    Transaction::factory()->count(3)->create([
        'date' => now()->startOfMonth()->addDay(),
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentTransactions.data', 3)
        );
});
