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

it('buckets the income-vs-expense trend by the requested granularity', function (): void {
    $user = User::factory()->create();

    Transaction::factory()->create(['date' => now()->startOfMonth(), 'amount' => 1000]);
    Transaction::factory()->create(['date' => now()->startOfMonth()->addDay(), 'amount' => -200]);

    foreach (['day', 'week', 'month'] as $trend) {
        $this->actingAs($user)
            ->get("/?range=this_month&trend={$trend}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.trend', $trend)
                ->has('monthlyTrends')
            );
    }
});

it('collapses the trend into a single bucket for the entire period', function (): void {
    $user = User::factory()->create();

    Transaction::factory()->count(3)->create(['date' => now()->startOfMonth(), 'amount' => 100]);

    $this->actingAs($user)
        ->get('/?range=this_month&trend=period')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.trend', 'period')
            ->has('monthlyTrends', 1)
        );
});

it('falls back to month for an invalid trend value', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/?trend=nonsense')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.trend', 'month'));
});

it('excludes transfers from income, expenses and spending', function (): void {
    $user = User::factory()->create();
    $date = now()->startOfMonth();

    // A genuine income + expense.
    Transaction::factory()->create(['date' => $date, 'amount' => 1000, 'category' => 'Income']);
    Transaction::factory()->create(['date' => $date, 'amount' => -200, 'category' => 'Groceries']);
    // A $500 transfer between accounts (outflow + inflow), tagged Transfer.
    Transaction::factory()->create(['date' => $date, 'amount' => -500, 'category' => 'Transfer', 'account' => 'Checking']);
    Transaction::factory()->create(['date' => $date, 'amount' => 500, 'category' => 'Transfer', 'account' => 'Savings']);

    $this->actingAs($user)
        ->get('/?range=this_month&trend=period')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // Income 1000 (not 1500), expenses 200 (not 700) — transfer excluded.
            ->where('monthlyTrends.0.income', fn ($v): bool => (float) $v === 1000.0)
            ->where('monthlyTrends.0.expenses', fn ($v): bool => (float) $v === 200.0)
            // Spending pie shows Groceries only, not Transfer.
            ->has('spendingByCategory', 1)
        );
});

it('filters transactions by category', function (): void {
    $user = User::factory()->create();
    $date = now()->startOfMonth();

    Transaction::factory()->create(['date' => $date, 'category' => 'Groceries', 'description' => 'Countdown']);
    Transaction::factory()->create(['date' => $date, 'category' => 'Transport', 'description' => 'Uber']);

    $this->actingAs($user)
        ->get('/?range=this_month&category=Groceries')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentTransactions.data', 1)
            ->where('recentTransactions.data.0.category', 'Groceries')
            ->where('filters.category', 'Groceries')
        );
});

it('filters to uncategorized transactions', function (): void {
    $user = User::factory()->create();
    $date = now()->startOfMonth();

    Transaction::factory()->create(['date' => $date, 'category' => 'Groceries']);
    Transaction::factory()->count(2)->create(['date' => $date, 'category' => null]);

    $this->actingAs($user)
        ->get('/?range=this_month&category=__uncategorized__')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('recentTransactions.data', 2));
});

it('paginates transactions in pages of 100', function (): void {
    $user = User::factory()->create();
    Transaction::factory()->count(150)->create(['date' => now()->startOfMonth()]);

    $this->actingAs($user)
        ->get('/?range=this_month')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentTransactions.data', 100)
            ->where('recentTransactions.per_page', 100)
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
