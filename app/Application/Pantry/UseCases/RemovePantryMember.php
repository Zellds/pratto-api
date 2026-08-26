<?php

// app/Application/Pantry/UseCases/RemovePantryMember.php

namespace App\Application\Pantry\UseCases;

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class RemovePantryMember
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
        private UserRepositoryInterface $users,
    ) {}

    public function __invoke(string $pantryId, string $ownerId, string $targetUsername): void
    {
        $pantryUlid = Ulid::fromString($pantryId);
        $ownerUlid = Ulid::fromString($ownerId);

        $pantry = $this->pantries->findById($pantryUlid);

        if ($pantry === null || ! $this->memberships->hasAccess($pantryUlid, $ownerUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $pantry->assertOwnedBy($ownerUlid);

        try {
            $usernameVo = Username::fromString($targetUsername);
        } catch (InvalidArgumentException) {
            throw UserNotFoundException::forUsernameString($targetUsername);
        }

        $target = $this->users->findByUsername($usernameVo);

        if ($target === null) {
            throw UserNotFoundException::forUsername($usernameVo);
        }

        $this->memberships->removeMember($pantryUlid, $target->id());
    }
}
