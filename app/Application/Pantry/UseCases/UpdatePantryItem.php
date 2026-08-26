<?php

// app/Application/Pantry/UseCases/UpdatePantryItem.php

namespace App\Application\Pantry\UseCases;

use App\Application\Pantry\DTOs\PantryItemOutput;
use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;

final readonly class UpdatePantryItem
{
    public function __construct(
        private PantryItemRepositoryInterface $items,
        private PantryMembershipRepositoryInterface $memberships,
    ) {}

    public function __invoke(
        string $itemId,
        string $actorId,
        ?bool $needsToBuy,
        ?float $quantity,
        ?MeasurementUnit $unit,
        ?bool $isFixed,
    ): PantryItemOutput {
        $itemUlid = Ulid::fromString($itemId);
        $actorUlid = Ulid::fromString($actorId);

        $item = $this->items->findById($itemUlid);

        if ($item === null || ! $this->memberships->hasAccess($item->pantryId(), $actorUlid)) {
            throw PantryNotFoundException::forId($itemUlid);
        }

        if ($needsToBuy !== null) {
            $item->toggleNeedsToBuy($needsToBuy);
        }

        if ($quantity !== null || $unit !== null) {
            $item->updateQuantity($quantity ?? $item->quantity(), $unit ?? $item->unit());
        }

        if ($isFixed !== null) {
            $item->markFixed($isFixed);
        }

        $this->items->save($item);

        return PantryItemOutput::fromDomain($item);
    }
}
