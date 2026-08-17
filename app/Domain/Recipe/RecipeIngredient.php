<?php

namespace App\Domain\Recipe;

use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use InvalidArgumentException;

final readonly class RecipeIngredient
{
    private function __construct(
        private Ulid $ingredientId,
        private float $quantity,
        private MeasurementUnit $unit,
        private int $position,
    ) {}

    public static function create(Ulid $ingredientId, float $quantity, MeasurementUnit $unit, int $position): self
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Ingredient quantity must be greater than zero.');
        }

        if ($position < 0) {
            throw new InvalidArgumentException('Ingredient position cannot be negative.');
        }

        return new self($ingredientId, $quantity, $unit, $position);
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

    public function position(): int
    {
        return $this->position;
    }

    public function scaledBy(float $ratio): self
    {
        return new self($this->ingredientId, $this->quantity * $ratio, $this->unit, $this->position);
    }
}
