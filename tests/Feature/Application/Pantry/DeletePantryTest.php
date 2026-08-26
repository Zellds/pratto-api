<?php

// tests/Feature/Application/Pantry/DeletePantryTest.php

use App\Application\Pantry\UseCases\DeletePantry;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes a pantry owned by the actor', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());

    app(DeletePantry::class)($pantry->id, $owner->value());

    expect(app(PantryRepositoryInterface::class)->findById(Ulid::fromString($pantry->id)))->toBeNull();
});

it('throws when the pantry does not exist', function () {
    app(DeletePantry::class)((string) new Symfony\Component\Uid\Ulid, anOwner()->value());
})->throws(PantryNotFoundException::class);

it('throws when a non-member tries to delete', function () {
    $pantry = aPantry(anOwner()->value());

    app(DeletePantry::class)($pantry->id, anOwner()->value());
})->throws(PantryNotFoundException::class);

it('throws when a member (not the owner) tries to delete', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $member = anOwner();
    $memberUsername = EloquentUser::query()->whereKey($member->value())->value('username');
    aPantryMember($pantry->id, $owner->value(), $memberUsername);

    app(DeletePantry::class)($pantry->id, $member->value());
})->throws(PantryNotOwnedException::class);
