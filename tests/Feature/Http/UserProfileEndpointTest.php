<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user profile', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/me');

    $response->assertOk()->assertJsonPath('username', 'gabriel');
});

it('updates the bio of the authenticated user', function () {
    $token = authenticatedToken($this);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/me', ['bio' => 'Cozinheiro amador.']);

    $response->assertOk()->assertJsonPath('bio', 'Cozinheiro amador.');
});

it('rejects unauthenticated access', function () {
    $this->getJson('/api/me')->assertStatus(401);
});
