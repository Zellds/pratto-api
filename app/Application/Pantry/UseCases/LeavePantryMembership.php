<?php

// app/Application/Pantry/UseCases/LeavePantryMembership.php

namespace App\Application\Pantry\UseCases;

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class LeavePantryMembership
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
    ) {}

    public function __invoke(string $pantryId, string $actorId): void
    {
        $pantryUlid = Ulid::fromString($pantryId);

        if ($this->pantries->findById($pantryUlid) === null) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $this->memberships->removeMember($pantryUlid, Ulid::fromString($actorId));
    }
}
