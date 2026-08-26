<?php

// app/Application/Pantry/UseCases/AddPantryItem.php

namespace App\Application\Pantry\UseCases;

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Application\Pantry\DTOs\PantryItemOutput;
use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Pantry\PantryItem;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;

final readonly class AddPantryItem
{
    public function __construct(
        private PantryRepositoryInterface $pantries,
        private PantryMembershipRepositoryInterface $memberships,
        private PantryItemRepositoryInterface $items,
        private ResolveIngredient $resolveIngredient,
    ) {}

    public function __invoke(
        string $pantryId,
        string $actorId,
        ?string $ingredientId,
        ?string $ingredientName,
        float $quantity,
        MeasurementUnit $unit,
        bool $isFixed,
    ): PantryItemOutput {
        $pantryUlid = Ulid::fromString($pantryId);
        $actorUlid = Ulid::fromString($actorId);

        if ($this->pantries->findById($pantryUlid) === null || ! $this->memberships->hasAccess($pantryUlid, $actorUlid)) {
            throw PantryNotFoundException::forId($pantryUlid);
        }

        $ingredient = ($this->resolveIngredient)(new ResolveIngredientInput($ingredientId, $ingredientName));
        $existing = $this->items->findByPantryAndIngredient($pantryUlid, $ingredient->id());

        if ($existing !== null) {
            $existing->updateQuantity($quantity, $unit);
            $existing->toggleNeedsToBuy(true);
            $this->items->save($existing);

            return PantryItemOutput::fromDomain($existing);
        }

        $item = PantryItem::create(Ulid::generate(), $pantryUlid, $ingredient->id(), $quantity, $unit, $isFixed);
        $this->items->save($item);

        return PantryItemOutput::fromDomain($item);
    }
}
