<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('renders the categories page with categories and rules', function (): void {
    Category::create(['name' => 'Groceries']);
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'countdown']);

    $this->actingAs($this->user)
        ->get('/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories', false)
            ->has('categories', 1)
            ->has('rules', 1)
        );
});

it('creates a category', function (): void {
    $this->actingAs($this->user)
        ->post('/categories', ['name' => 'Dining'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Category::where('name', 'Dining')->exists())->toBeTrue();
});

it('rejects a duplicate category', function (): void {
    Category::create(['name' => 'Dining']);

    $this->actingAs($this->user)
        ->post('/categories', ['name' => 'Dining'])
        ->assertSessionHasErrors('name');
});

it('renames a category and cascades to transactions and rules', function (): void {
    $category = Category::create(['name' => 'Old']);
    Transaction::factory()->create(['category' => 'Old']);
    CategoryRule::create(['category' => 'Old', 'pattern' => 'x']);

    $this->actingAs($this->user)
        ->patch("/categories/{$category->id}", ['name' => 'New'])
        ->assertRedirect();

    expect(Transaction::where('category', 'New')->count())->toBe(1);
    expect(CategoryRule::where('category', 'New')->count())->toBe(1);
});

it('deletes a category', function (): void {
    $category = Category::create(['name' => 'Temp']);

    $this->actingAs($this->user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect();

    expect(Category::count())->toBe(0);
});

it('creates a rule and applies it to matching transactions', function (): void {
    $match = Transaction::factory()->create(['category' => null, 'description' => 'COUNTDOWN METRO', 'category_locked' => false]);

    $this->actingAs($this->user)
        ->post('/category-rules', ['category' => 'Groceries', 'pattern' => 'countdown'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($match->fresh()->category)->toBe('Groceries');
});

it('updates a rule and re-applies it', function (): void {
    $rule = CategoryRule::create(['category' => 'Groceries', 'pattern' => 'countdown']);
    $t = Transaction::factory()->create(['category' => null, 'description' => 'NEW WORLD', 'category_locked' => false]);

    $this->actingAs($this->user)
        ->patch("/category-rules/{$rule->id}", ['category' => 'Groceries', 'pattern' => 'new world'])
        ->assertRedirect();

    expect($t->fresh()->category)->toBe('Groceries');
});

it('deletes a rule', function (): void {
    $rule = CategoryRule::create(['category' => 'Groceries', 'pattern' => 'x']);

    $this->actingAs($this->user)
        ->delete("/category-rules/{$rule->id}")
        ->assertRedirect();

    expect(CategoryRule::count())->toBe(0);
});

it('recategorizes via the recategorize endpoint', function (): void {
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'countdown']);
    $t = Transaction::factory()->create(['category' => null, 'description' => 'COUNTDOWN', 'category_locked' => false]);

    $this->actingAs($this->user)
        ->post('/categories/recategorize')
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($t->fresh()->category)->toBe('Groceries');
});

it('does not overwrite locked transactions when applying rules', function (): void {
    CategoryRule::create(['category' => 'Groceries', 'pattern' => 'countdown']);
    $locked = Transaction::factory()->create(['category' => 'Manual', 'description' => 'COUNTDOWN', 'category_locked' => true]);

    $this->actingAs($this->user)->post('/categories/recategorize');

    expect($locked->fresh()->category)->toBe('Manual');
});
