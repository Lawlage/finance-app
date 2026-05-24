<?php

declare(strict_types=1);

use App\Models\User;

it('shows the login page', function (): void {
    $this->get('/login')->assertOk();
});

it('authenticates a user with valid credentials', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('validates required fields on login', function (): void {
    $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
});

it('logs out an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
