<?php

use App\Application\User\UseCases\SetPassword;
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
