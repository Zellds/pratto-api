<?php

// app/Application/Pantry/UseCases/ListPantryItems.php

namespace App\Application\Pantry\UseCases;

use App\Application\Pantry\DTOs\PantryItemOutput;
use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ListPantryItems
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
        private PantryItemRepositoryInterface $items,
    ) {}

    /**
     * @return list<PantryItemOutput>
     */
    public function __invoke(string $pantryId, string $actorId): array
    {
        $pantryUlid = Ulid::fromString($pantryId);
        $actorUlid = Ulid::fromString($actorId);

        if ($this->pantries->findById($pantryUlid) === null || ! $this->memberships->hasAccess($pantryUlid, $actorUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        return array_map(
            PantryItemOutput::fromDomain(...),
            $this->items->forPantry($pantryUlid),
        );
    }
}
