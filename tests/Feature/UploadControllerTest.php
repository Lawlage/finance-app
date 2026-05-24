<?php

declare(strict_types=1);

use App\Jobs\ProcessUploadedStatement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('shows the upload page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/upload')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Upload', false));
});

it('uploads a csv file and dispatches processing job', function (): void {
    Queue::fake([ProcessUploadedStatement::class]);
    Storage::fake('local');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('statement.csv', 100, 'text/csv');

    $this->actingAs($user)
        ->post('/upload', [
            'statement' => $file,
            'account' => 'Checking',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(ProcessUploadedStatement::class);
});

it('validates required upload fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/upload', [])
        ->assertSessionHasErrors(['statement', 'account']);
});

it('rejects invalid file types', function (): void {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('statement.txt', 100, 'text/plain');

    $this->actingAs($user)
        ->post('/upload', [
            'statement' => $file,
            'account' => 'Checking',
        ])
        ->assertSessionHasErrors('statement');
});
