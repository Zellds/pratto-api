<?php

// tests/Feature/Application/Pantry/RemovePantryMemberTest.php

use App\Application\Pantry\UseCases\RemovePantryMember;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\Shared\Ulid;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes a member by username', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $member = anOwner();
    $memberUsername = EloquentUser::query()->whereKey($member->value())->value('username');
    aPantryMember($pantry->id, $owner->value(), $memberUsername);

    app(RemovePantryMember::class)($pantry->id, $owner->value(), $memberUsername);

    expect(app(PantryMembershipRepositoryInterface::class)->isMember(Ulid::fromString($pantry->id), $member))->toBeFalse();
});

it('is idempotent when removing someone who is not a member', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $strangerUsername = EloquentUser::query()->whereKey(anOwner()->value())->value('username');

    app(RemovePantryMember::class)($pantry->id, $owner->value(), $strangerUsername);

    expect(true)->toBeTrue();
});

it('throws when the target username does not exist', function () {
    $pantry = aPantry(anOwner()->value());

    app(RemovePantryMember::class)($pantry->id, $pantry->ownerId, 'nao_existe');
})->throws(UserNotFoundException::class);

it('throws UserNotFoundException (not InvalidArgumentException) for a malformed target username', function () {
    $pantry = aPantry(anOwner()->value());

    app(RemovePantryMember::class)($pantry->id, $pantry->ownerId, 'Gabriel');
})->throws(UserNotFoundException::class);

it('throws when a non-owner tries to remove a member', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $memberA = anOwner();
    $memberB = anOwner();
    $memberAUsername = EloquentUser::query()->whereKey($memberA->value())->value('username');
    $memberBUsername = EloquentUser::query()->whereKey($memberB->value())->value('username');
    aPantryMember($pantry->id, $owner->value(), $memberAUsername);
    aPantryMember($pantry->id, $owner->value(), $memberBUsername);

    app(RemovePantryMember::class)($pantry->id, $memberA->value(), $memberBUsername);
})->throws(PantryNotOwnedException::class);
