<?php

namespace App\Domain\Ingredient;

use App\Domain\Shared\Ulid;

interface IngredientRepositoryInterface
{
    public function findById(Ulid $id): ?Ingredient;

    public function findByNormalizedName(string $normalizedName): ?Ingredient;

    /**
     * Ranked by prefix match and trigram similarity against $term, most relevant first.
     *
     * @return list<Ingredient>
     */
    public function search(string $term, int $limit = 10): array;

    public function save(Ingredient $ingredient): void;
}
