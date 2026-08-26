<?php

namespace App\Domain\Pantry;

use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use InvalidArgumentException;

/**
 * A single ingredient line in a pantry's shared list. Independent
 * aggregate (not a child saved in bulk with Pantry) because items are
 * toggled/edited one at a time, far more often than the pantry itself
 * changes — same reasoning that already keeps Comment independent of
 * Recipe rather than following RecipeIngredient/RecipeStep's pattern.
 *
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
final class PantryItem
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $pantryId,
        private readonly Ulid $ingredientId,
        private float $quantity,
        private MeasurementUnit $unit,
        private bool $needsToBuy,
        private bool $isFixed,
    ) {}

    public static function create(Ulid $id, Ulid $pantryId, Ulid $ingredientId, float $quantity, MeasurementUnit $unit, bool $isFixed): self
    {
        $item = new self($id, $pantryId, $ingredientId, 0.0, $unit, true, $isFixed);
        $item->updateQuantity($quantity, $unit);

        return $item;
    }

    public static function reconstitute(
        Ulid $id,
        Ulid $pantryId,
        Ulid $ingredientId,
        float $quantity,
        MeasurementUnit $unit,
        bool $needsToBuy,
        bool $isFixed,
    ): self {
        return new self($id, $pantryId, $ingredientId, $quantity, $unit, $needsToBuy, $isFixed);
    }

    public function toggleNeedsToBuy(bool $needsToBuy): void
    {
        $this->needsToBuy = $needsToBuy;
    }

    public function updateQuantity(float $quantity, MeasurementUnit $unit): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Pantry item quantity must be greater than zero.');
        }

        $this->quantity = $quantity;
        $this->unit = $unit;
    }

    public function markFixed(bool $isFixed): void
    {
        $this->isFixed = $isFixed;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function pantryId(): Ulid
    {
        return $this->pantryId;
    }

    public function ingredientId(): Ulid
    {
        return $this->ingredientId;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unit(): MeasurementUnit
    {
        return $this->unit;
    }

    public function needsToBuy(): bool
    {
        return $this->needsToBuy;
    }

    public function isFixed(): bool
    {
        return $this->isFixed;
    }
}
