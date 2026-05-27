<?php

declare(strict_types=1);

use App\Models\McpAccessLog;
use App\Models\ReplacementRule;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('shows the privacy page with rules, mode, accounts and audit log', function (): void {
    $user = User::factory()->create();
    ReplacementRule::create(['value' => '38-9009-0123456-00', 'label' => 'Joint Savings']);
    Transaction::factory()->create(['account' => 'Checking']);
    McpAccessLog::record('resource', 'finance://transactions', '{"ok":true}');

    $this->actingAs($user)
        ->get('/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Privacy', false)
            ->has('rules', 1)
            ->has('auditLog', 1)
            ->where('fallbackMode', 'pseudonym')
        );
});

it('adds a replacement rule', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/replacement-rules', ['value' => 'Smith J R', 'label' => 'Landlord'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ReplacementRule::count())->toBe(1);
    expect(ReplacementRule::first()->label)->toBe('Landlord');
});

it('stores the replacement value encrypted', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/replacement-rules', ['value' => '38-9009-0123456-00', 'label' => 'Joint']);

    $raw = DB::table('replacement_rules')->value('value');
    expect($raw)->not->toContain('38-9009-0123456-00');
    expect(ReplacementRule::first()->value)->toBe('38-9009-0123456-00');
});

it('updates a replacement rule', function (): void {
    $user = User::factory()->create();
    $rule = ReplacementRule::create(['value' => '38-9009-0123456-00', 'label' => 'Old']);

    $this->actingAs($user)
        ->patch("/replacement-rules/{$rule->id}", [
            'value' => '38-9009-0123456-00',
            'label' => 'Joint Savings',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($rule->fresh()->label)->toBe('Joint Savings');
});

it('deletes a replacement rule', function (): void {
    $user = User::factory()->create();
    $rule = ReplacementRule::create(['value' => 'x', 'label' => 'y']);

    $this->actingAs($user)
        ->delete("/replacement-rules/{$rule->id}")
        ->assertRedirect();

    expect(ReplacementRule::count())->toBe(0);
});

it('updates the fallback mode setting', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/privacy/settings', ['fallback_mode' => 'redact'])
        ->assertRedirect();

    expect(Setting::get('fallback_mode'))->toBe('redact');
});

it('rejects an invalid fallback mode', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/privacy/settings', ['fallback_mode' => 'nonsense'])
        ->assertSessionHasErrors('fallback_mode');
});

it('requires authentication', function (): void {
    $this->get('/privacy')->assertRedirect('/login');
});
