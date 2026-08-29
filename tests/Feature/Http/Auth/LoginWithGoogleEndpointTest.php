<?php

use App\Application\User\UseCases\BanUser;
use App\Domain\User\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in via google and returns a token', function () {
    fakeGoogleVerifier(aGoogleIdentity(name: 'Http Jane'));

    $response = $this->postJson('/api/login/google', ['id_token' => 'whatever']);

    $response->assertOk()->assertJsonStructure(['token']);
});

it('rejects an invalid google token', function () {
    fakeInvalidGoogleVerifier();

    $this->postJson('/api/login/google', ['id_token' => 'bad'])->assertStatus(422);
});

it('requires id_token', function () {
    $this->postJson('/api/login/google', [])->assertStatus(422);
});

it('rejects login via google for a banned user', function () {
    $identity = aGoogleIdentity(name: 'Ban Http');
    fakeGoogleVerifier($identity);
    $this->postJson('/api/login/google', ['id_token' => 'first']);
    $user = app(UserRepositoryInterface::class)->findByGoogleId($identity->googleId);
    $admin = anAdmin();
    app(BanUser::class)($user->username()->value(), $admin->value(), 'Motivo.');
    $this->app['auth']->forgetGuards();

    $this->postJson('/api/login/google', ['id_token' => 'first'])->assertStatus(403);
});
