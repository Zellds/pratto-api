<?php

// app/Application/Pantry/UseCases/DeletePantry.php

namespace App\Application\Pantry\UseCases;

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class DeletePantry
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
    ) {}

    public function __invoke(string $pantryId, string $actorId): void
    {
        $pantryUlid = Ulid::fromString($pantryId);
        $actorUlid = Ulid::fromString($actorId);

        $pantry = $this->pantries->findById($pantryUlid);

        if ($pantry === null || ! $this->memberships->hasAccess($pantryUlid, $actorUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $pantry->assertOwnedBy($actorUlid);

        $this->pantries->delete($pantryUlid);
    }
}
