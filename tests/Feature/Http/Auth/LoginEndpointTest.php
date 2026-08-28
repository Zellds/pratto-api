<?php

use App\Application\User\UseCases\BanUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in with valid credentials and returns a token', function () {
    $this->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    $response = $this->postJson('/api/login', [
        'username' => 'gabriel',
        'password' => 'senha-forte-123',
    ]);

    $response->assertOk()->assertJsonStructure(['token']);
});

it('rejects invalid credentials', function () {
    $this->postJson('/api/login', [
        'username' => 'ninguem',
        'password' => 'errada',
    ])->assertStatus(422);
});

it('logs out and invalidates the token', function () {
    $this->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);
    $login = $this->postJson('/api/login', ['username' => 'gabriel', 'password' => 'senha-forte-123']);
    $token = $login->json('token');

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/logout');

    $response->assertNoContent();

    // Sanctum's request guard caches the resolved user for the lifetime of the
    // application instance. Without forgetting it here, the next request reusing
    // this same container would still see the just-revoked token as authenticated.
    $this->app->forgetInstance('auth');

    $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/me');
    $me->assertStatus(401);
});

it('rejects login for a banned user with the ban reason', function () {
    $admin = anAdmin();
    $this->postJson('/api/register', [
        'username' => 'gabriel_banido',
        'display_name' => 'Gabriel',
        'password' => 'senha-forte-123',
    ]);
    app(BanUser::class)('gabriel_banido', $admin->value(), 'Conteúdo impróprio.');

    $response = $this->postJson('/api/login', ['username' => 'gabriel_banido', 'password' => 'senha-forte-123']);

    $response->assertStatus(403)->assertJsonPath('message', 'Account banned: Conteúdo impróprio.');
});
