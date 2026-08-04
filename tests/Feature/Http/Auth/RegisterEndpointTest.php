<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'username', 'displayName', 'bio']])
        ->assertJsonPath('user.username', 'gabriel');
});

it('rejects registration with a duplicate username', function () {
    $this->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ])->assertCreated();

    $this->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Outro Nome',
        'password' => 'outra-senha-123',
    ])->assertStatus(422);
});

it('rejects registration with an invalid-format username', function () {
    $response = $this->postJson('/api/register', [
        'username' => 'Gabriel Medeiros',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('username');
});
