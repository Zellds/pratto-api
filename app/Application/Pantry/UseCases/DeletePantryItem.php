<?php

// app/Application/Pantry/UseCases/DeletePantryItem.php

namespace App\Application\Pantry\UseCases;

use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class DeletePantryItem
{
    public function __construct(
        private PantryItemRepositoryInterface $items,
        private PantryMembershipRepositoryInterface $memberships,
    ) {}

    public function __invoke(string $itemId, string $actorId): void
    {
        $itemUlid = Ulid::fromString($itemId);
        $actorUlid = Ulid::fromString($actorId);

        $item = $this->items->findById($itemUlid);

        if ($item === null || ! $this->memberships->hasAccess($item->pantryId(), $actorUlid)) {
            throw PantryNotFoundException::forId($itemUlid);
        }

        $this->items->delete($itemUlid);
    }
}
