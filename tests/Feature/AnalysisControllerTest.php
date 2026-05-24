<?php

declare(strict_types=1);

use App\Jobs\RunSpendingAnalysis;
use App\Models\AnalysisRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('shows analysis index to authenticated users', function (): void {
    $user = User::factory()->create();
    AnalysisRun::factory()->count(2)->create();

    $this->actingAs($user)
        ->get('/analysis')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Analysis', false)
            ->has('analyses', 2)
        );
});

it('dispatches analysis job with valid dates', function (): void {
    Queue::fake([RunSpendingAnalysis::class]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analysis', [
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(RunSpendingAnalysis::class);
});

it('validates required fields for analysis', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analysis', [])
        ->assertSessionHasErrors(['period_start', 'period_end']);
});

it('validates period_end is after period_start', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analysis', [
            'period_start' => '2026-01-31',
            'period_end' => '2026-01-01',
        ])
        ->assertSessionHasErrors('period_end');
});

it('shows a single analysis run', function (): void {
    $user = User::factory()->create();
    $analysis = AnalysisRun::factory()->create();

    $this->actingAs($user)
        ->get("/analysis/{$analysis->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AnalysisShow', false)
            ->has('analysis')
        );
});
