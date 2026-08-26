<?php

// tests/Feature/Application/Pantry/EloquentPantryRepositoryTest.php

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Pantry;
use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a pantry and finds it back', function () {
    $repository = app(PantryRepositoryInterface::class);
    $owner = anOwner();

    $pantry = Pantry::create(Ulid::generate(), $owner, 'Minha despensa');
    $repository->save($pantry);

    $found = $repository->findById($pantry->id());

    expect($found)->not->toBeNull()
        ->and($found->name())->toBe('Minha despensa')
        ->and($found->ownerId()->equals($owner))->toBeTrue();
});

it('deletes a pantry', function () {
    $repository = app(PantryRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    $repository->save($pantry);

    $repository->delete($pantry->id());

    expect($repository->findById($pantry->id()))->toBeNull();
});

it('counts owned pantries plus pantries the user is a member of', function () {
    $repository = app(PantryRepositoryInterface::class);
    $memberships = app(PantryMembershipRepositoryInterface::class);
    $user = anOwner();

    $repository->save(Pantry::create(Ulid::generate(), $user, 'Própria 1'));
    $repository->save(Pantry::create(Ulid::generate(), $user, 'Própria 2'));

    $othersPantry = Pantry::create(Ulid::generate(), anOwner(), 'De outro dono');
    $repository->save($othersPantry);
    $memberships->save(PantryMembership::create(Ulid::generate(), $othersPantry->id(), $user));

    expect($repository->countPantriesForUser($user))->toBe(3);
});

it('lists owned pantries plus pantries the user is a member of', function () {
    $repository = app(PantryRepositoryInterface::class);
    $memberships = app(PantryMembershipRepositoryInterface::class);
    $user = anOwner();

    $own = Pantry::create(Ulid::generate(), $user, 'Própria');
    $repository->save($own);

    $othersPantry = Pantry::create(Ulid::generate(), anOwner(), 'De outro dono');
    $repository->save($othersPantry);
    $memberships->save(PantryMembership::create(Ulid::generate(), $othersPantry->id(), $user));

    $names = collect($repository->pantriesForUser($user))->map(fn (Pantry $p) => $p->name())->sort()->values()->all();

    expect($names)->toBe(['De outro dono', 'Própria']);
});
