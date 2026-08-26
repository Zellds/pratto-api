<?php

use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\Pantry\Pantry;
use App\Domain\Shared\Ulid;

it('creates a pantry with the given owner and name', function () {
    $ownerId = Ulid::generate();

    $pantry = Pantry::create(Ulid::generate(), $ownerId, 'Minha despensa');

    expect($pantry->ownerId()->equals($ownerId))->toBeTrue()
        ->and($pantry->name())->toBe('Minha despensa');
});

it('rejects an empty name', function () {
    Pantry::create(Ulid::generate(), Ulid::generate(), '   ');
})->throws(InvalidArgumentException::class);

it('does not throw when the owner asserts ownership', function () {
    $ownerId = Ulid::generate();
    $pantry = Pantry::create(Ulid::generate(), $ownerId, 'Minha despensa');

    $pantry->assertOwnedBy($ownerId);

    expect(true)->toBeTrue();
});

it('throws when a non-owner asserts ownership', function () {
    $pantry = Pantry::create(Ulid::generate(), Ulid::generate(), 'Minha despensa');

    $pantry->assertOwnedBy(Ulid::generate());
})->throws(PantryNotOwnedException::class);

it('reconstitutes with the same field order as create', function () {
    $id = Ulid::generate();
    $ownerId = Ulid::generate();

    $pantry = Pantry::reconstitute($id, $ownerId, 'Casa da praia');

    expect($pantry->id()->equals($id))->toBeTrue()
        ->and($pantry->ownerId()->equals($ownerId))->toBeTrue()
        ->and($pantry->name())->toBe('Casa da praia');
});
