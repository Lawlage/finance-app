<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Transaction;
use App\Models\User;

it('deletes an import and its transactions', function (): void {
    $user = User::factory()->create();
    $import = Import::create(['filename' => 'statement.csv', 'account' => 'Checking', 'transaction_count' => 2]);
    Transaction::factory()->count(2)->create(['import_id' => $import->id]);

    $this->actingAs($user)
        ->delete("/imports/{$import->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Import::count())->toBe(0);
    expect(Transaction::where('import_id', $import->id)->count())->toBe(0);
});

it('requires authentication', function (): void {
    $import = Import::create(['filename' => 'x.csv', 'account' => 'Checking', 'transaction_count' => 0]);

    $this->delete("/imports/{$import->id}")->assertRedirect('/login');
});
