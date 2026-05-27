<?php

declare(strict_types=1);

use App\Models\JobStatus;
use App\Models\User;

it('lists recent job statuses as json', function (): void {
    $user = User::factory()->create();
    JobStatus::start('categorize', 'Working...');

    $this->actingAs($user)
        ->getJson('/api/job-statuses')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['type' => 'categorize', 'status' => 'pending']);
});

it('excludes statuses older than an hour', function (): void {
    $user = User::factory()->create();
    $old = JobStatus::start('import', 'Old job');
    $old->forceFill(['created_at' => now()->subHours(2)])->save();

    $this->actingAs($user)
        ->getJson('/api/job-statuses')
        ->assertOk()
        ->assertJsonCount(0);
});

it('dismisses a job status', function (): void {
    $user = User::factory()->create();
    $status = JobStatus::start('analysis', 'Done');

    $this->actingAs($user)
        ->deleteJson("/api/job-statuses/{$status->id}")
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(JobStatus::count())->toBe(0);
});
