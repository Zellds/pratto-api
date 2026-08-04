<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function authenticatedToken(TestCase $test): string
{
    $test->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    return $test->postJson('/api/login', [
        'username' => 'gabriel',
        'password' => 'senha-forte-123',
    ])->json('token');
}

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
