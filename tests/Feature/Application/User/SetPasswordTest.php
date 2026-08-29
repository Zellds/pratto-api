<?php

use App\Application\User\UseCases\SetPassword;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Username;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets a password for the authenticated user, enabling password login afterward', function () {
    $owner = anOwner();

    app(SetPassword::class)($owner->value(), 'nova-senha-123');

    $username = app(UserRepositoryInterface::class)->findById($owner)->username()->value();
    $found = app(UserRepositoryInterface::class)->verifyCredentials(Username::fromString($username), 'nova-senha-123');
    expect($found)->not->toBeNull();
});

it('revokes every other access token when setting a password', function () {
    $owner = anOwner();
    $issuer = app(AccessTokenIssuerInterface::class);
    $token = $issuer->issueFor($owner);

    app(SetPassword::class)($owner->value(), 'nova-senha-123');

    // Sanctum's request guard caches the resolved user for the lifetime of the
    // application instance. Without forgetting it here, the next request reusing
    // this same container would still see the just-revoked token as authenticated.
    $this->app->forgetInstance('auth');

    $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/me');
    $me->assertStatus(401);
});
