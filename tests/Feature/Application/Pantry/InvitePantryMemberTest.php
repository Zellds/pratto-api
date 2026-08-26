<?php

// tests/Feature/Application/Pantry/InvitePantryMemberTest.php

use App\Application\Pantry\UseCases\InvitePantryMember;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Exceptions\CannotInviteSelfException;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryMemberLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\Exceptions\PantryNotOwnedException;
use App\Domain\Shared\Ulid;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('invites a user by username', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $invitee = anOwner();
    $inviteeUsername = EloquentUser::query()->whereKey($invitee->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $owner->value(), $inviteeUsername);

    expect(app(PantryMembershipRepositoryInterface::class)->isMember(Ulid::fromString($pantry->id), $invitee))->toBeTrue();
});

it('is idempotent when inviting the same user twice', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $invitee = anOwner();
    $inviteeUsername = EloquentUser::query()->whereKey($invitee->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $owner->value(), $inviteeUsername);
    app(InvitePantryMember::class)($pantry->id, $owner->value(), $inviteeUsername);

    expect(app(PantryMembershipRepositoryInterface::class)->countMembers(Ulid::fromString($pantry->id)))->toBe(1);
});

it('throws when the invitee username does not exist', function () {
    $pantry = aPantry(anOwner()->value());

    app(InvitePantryMember::class)($pantry->id, $pantry->ownerId, 'nao_existe');
})->throws(UserNotFoundException::class);

it('throws when the owner invites themselves', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $ownerUsername = EloquentUser::query()->whereKey($owner->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $owner->value(), $ownerUsername);
})->throws(CannotInviteSelfException::class);

it('throws when the pantry does not exist', function () {
    app(InvitePantryMember::class)((string) new Symfony\Component\Uid\Ulid, anOwner()->value(), 'gabriel');
})->throws(PantryNotFoundException::class);

it('throws when a non-owner tries to invite', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $member = anOwner();
    aPantryMember($pantry->id, $owner->value(), EloquentUser::query()->whereKey($member->value())->value('username'));
    $invitee = anOwner();
    $inviteeUsername = EloquentUser::query()->whereKey($invitee->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $member->value(), $inviteeUsername);
})->throws(PantryNotOwnedException::class);

it('throws when the pantry already has the maximum number of members', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    for ($i = 0; $i < 20; $i++) {
        $member = anOwner();
        aPantryMember($pantry->id, $owner->value(), EloquentUser::query()->whereKey($member->value())->value('username'));
    }
    $lastInvitee = anOwner();
    $lastInviteeUsername = EloquentUser::query()->whereKey($lastInvitee->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $owner->value(), $lastInviteeUsername);
})->throws(PantryMemberLimitExceededException::class);

it('throws UserNotFoundException (not InvalidArgumentException) for a malformed invitee username', function () {
    $pantry = aPantry(anOwner()->value());

    app(InvitePantryMember::class)($pantry->id, $pantry->ownerId, 'Gabriel');
})->throws(UserNotFoundException::class);

it('throws when the invitee already has the maximum number of pantries', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $invitee = anOwner();
    for ($i = 0; $i < 5; $i++) {
        aPantry($invitee->value(), "Despensa {$i}");
    }
    $inviteeUsername = EloquentUser::query()->whereKey($invitee->value())->value('username');

    app(InvitePantryMember::class)($pantry->id, $owner->value(), $inviteeUsername);
})->throws(PantryLimitExceededException::class);
