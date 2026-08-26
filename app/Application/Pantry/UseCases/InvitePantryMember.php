<?php

// app/Application/Pantry/UseCases/InvitePantryMember.php

namespace App\Application\Pantry\UseCases;

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\CannotInviteSelfException;
use App\Domain\Pantry\Exceptions\PantryLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryMemberLimitExceededException;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\PantryLimits;
use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class InvitePantryMember
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
        private UserRepositoryInterface $users,
    ) {}

    public function __invoke(string $pantryId, string $ownerId, string $inviteeUsername): void
    {
        $pantryUlid = Ulid::fromString($pantryId);
        $ownerUlid = Ulid::fromString($ownerId);

        $pantry = $this->pantries->findById($pantryUlid);

        if ($pantry === null || ! $this->memberships->hasAccess($pantryUlid, $ownerUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $pantry->assertOwnedBy($ownerUlid);

        try {
            $usernameVo = Username::fromString($inviteeUsername);
        } catch (InvalidArgumentException) {
            throw UserNotFoundException::forUsernameString($inviteeUsername);
        }

        $invitee = $this->users->findByUsername($usernameVo);

        if ($invitee === null) {
            throw UserNotFoundException::forUsername($usernameVo);
        }

        if ($invitee->id()->equals($ownerUlid)) {
            throw CannotInviteSelfException::forUser($ownerUlid);
        }

        if ($this->memberships->isMember($pantryUlid, $invitee->id())) {
            return;
        }

        if ($this->memberships->countMembers($pantryUlid) >= PantryLimits::MAX_MEMBERS_PER_PANTRY) {
            throw PantryMemberLimitExceededException::forPantry($pantryUlid);
        }

        if ($this->pantries->countPantriesForUser($invitee->id()) >= PantryLimits::MAX_PANTRIES_PER_USER) {
            throw PantryLimitExceededException::forUser($invitee->id());
        }

        $this->memberships->save(PantryMembership::create(Ulid::generate(), $pantryUlid, $invitee->id()));
    }
}
