<?php

namespace App\Domain\Pantry\Contracts;

use App\Domain\Pantry\PantryItem;
use App\Domain\Shared\Ulid;

interface PantryItemRepositoryInterface
{
    public function findById(Ulid $id): ?PantryItem;

    public function findByPantryAndIngredient(Ulid $pantryId, Ulid $ingredientId): ?PantryItem;

    public function save(PantryItem $item): void;

    public function delete(Ulid $id): void;

    /**
     * @return list<PantryItem>
     */
    public function forPantry(Ulid $pantryId): array;
}
