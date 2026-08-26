<?php

// tests/Feature/Application/Pantry/EloquentPantryMembershipRepositoryTest.php

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Pantry;
use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grants access to the owner without a membership row', function () {
    $repository = app(PantryMembershipRepositoryInterface::class);
    $owner = anOwner();
    $pantry = Pantry::create(Ulid::generate(), $owner, 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);

    expect($repository->hasAccess($pantry->id(), $owner))->toBeTrue()
        ->and($repository->isMember($pantry->id(), $owner))->toBeFalse();
});

it('grants access to an invited member', function () {
    $repository = app(PantryMembershipRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $member = anOwner();

    $repository->save(PantryMembership::create(Ulid::generate(), $pantry->id(), $member));

    expect($repository->hasAccess($pantry->id(), $member))->toBeTrue()
        ->and($repository->isMember($pantry->id(), $member))->toBeTrue();
});

it('denies access to a stranger', function () {
    $repository = app(PantryMembershipRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);

    expect($repository->hasAccess($pantry->id(), anOwner()))->toBeFalse();
});

it('removes a member; removing a non-member is a no-op', function () {
    $repository = app(PantryMembershipRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $member = anOwner();
    $repository->save(PantryMembership::create(Ulid::generate(), $pantry->id(), $member));

    $repository->removeMember($pantry->id(), $member);
    $repository->removeMember($pantry->id(), $member);

    expect($repository->isMember($pantry->id(), $member))->toBeFalse();
});

it('counts members and lists their ids', function () {
    $repository = app(PantryMembershipRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $memberA = anOwner();
    $memberB = anOwner();
    $repository->save(PantryMembership::create(Ulid::generate(), $pantry->id(), $memberA));
    $repository->save(PantryMembership::create(Ulid::generate(), $pantry->id(), $memberB));

    expect($repository->countMembers($pantry->id()))->toBe(2)
        ->and(collect($repository->memberIdsFor($pantry->id()))->map(fn (Ulid $id) => $id->value())->sort()->values()->all())
        ->toBe(collect([$memberA, $memberB])->map(fn (Ulid $id) => $id->value())->sort()->values()->all());
});
