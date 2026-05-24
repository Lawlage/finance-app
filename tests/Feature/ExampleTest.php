<?php

declare(strict_types=1);

it('redirects unauthenticated users to login', function (): void {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

it('shows the login page', function (): void {
    $response = $this->get('/login');

    $response->assertStatus(200);
});
