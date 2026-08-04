<?php

use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\User;
use App\Domain\User\Username;

it('registers a new user with the given identity', function () {
    $id = Ulid::generate();
    $username = Username::fromString('gabriel_medeiros');
    $displayName = DisplayName::fromString('Gabriel Medeiros');

    $user = User::register($id, $username, $displayName);

    expect($user->id()->equals($id))->toBeTrue()
        ->and($user->username()->value())->toBe('gabriel_medeiros')
        ->and($user->displayName()->value())->toBe('Gabriel Medeiros')
        ->and($user->bio())->toBeNull();
});

it('updates the bio', function () {
    $user = User::register(Ulid::generate(), Username::fromString('gabriel'), DisplayName::fromString('Gabriel'));

    $user->updateBio('Cozinheiro amador, apaixonado por risoto.');

    expect($user->bio())->toBe('Cozinheiro amador, apaixonado por risoto.');
});

it('rejects a bio longer than 280 characters', function () {
    $user = User::register(Ulid::generate(), Username::fromString('gabriel'), DisplayName::fromString('Gabriel'));

    $user->updateBio(str_repeat('a', 281));
})->throws(InvalidArgumentException::class);
