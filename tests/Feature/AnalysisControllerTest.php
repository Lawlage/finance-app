<?php

declare(strict_types=1);

use App\Models\AnalysisRun;
use App\Models\User;

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

it('no longer exposes a dispatch endpoint', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analysis', [
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ])
        ->assertStatus(405);
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
