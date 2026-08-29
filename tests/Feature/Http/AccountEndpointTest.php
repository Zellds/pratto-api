<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links a google account to the authenticated user', function () {
    $token = authenticatedToken($this);
    fakeGoogleVerifier(aGoogleIdentity(googleId: 'http-link-1'));

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/me/google', ['id_token' => 'whatever'])
        ->assertNoContent();
});

it('rejects linking without authentication', function () {
    $this->patchJson('/api/me/google', ['id_token' => 'whatever'])->assertStatus(401);
});

it('rejects linking a google account already linked to someone else', function () {
    $tokenA = authenticatedTokenFor($this, 'account_a');
    fakeGoogleVerifier(aGoogleIdentity(googleId: 'http-link-2'));
    $this->withHeader('Authorization', "Bearer {$tokenA}")->patchJson('/api/me/google', ['id_token' => 'x']);

    $tokenB = authenticatedTokenFor($this, 'account_b');
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->patchJson('/api/me/google', ['id_token' => 'x'])
        ->assertStatus(409);
});

it('sets a password for the authenticated user, enabling password login afterward', function () {
    $token = authenticatedTokenFor($this, 'sets_own_password');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/me/password', ['password' => 'nova-senha-longa'])
        ->assertNoContent();

    $this->app['auth']->forgetGuards();
    $this->postJson('/api/login', ['username' => 'sets_own_password', 'password' => 'nova-senha-longa'])->assertOk();
});

it('rejects a password shorter than 8 characters', function () {
    $token = authenticatedToken($this);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/me/password', ['password' => 'short'])
        ->assertStatus(422);
});
