<?php

// app/Application/Pantry/DTOs/PantryItemOutput.php

namespace App\Application\Pantry\DTOs;

use App\Domain\Pantry\PantryItem;

final readonly class PantryItemOutput
{
    public function __construct(
        public string $id,
        public string $pantryId,
        public string $ingredientId,
        public float $quantity,
        public string $unit,
        public bool $needsToBuy,
        public bool $isFixed,
    ) {}

    public static function fromDomain(PantryItem $item): self
    {
        return new self(
            $item->id()->value(),
            $item->pantryId()->value(),
            $item->ingredientId()->value(),
            $item->quantity(),
            $item->unit()->value,
            $item->needsToBuy(),
            $item->isFixed(),
        );
    }
}
