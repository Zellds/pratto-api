<?php

namespace App\Domain\Ingredient\Contracts;

use App\Domain\Ingredient\Ingredient;
use App\Domain\Shared\Ulid;

interface IngredientRepositoryInterface
{
    public function findById(Ulid $id): ?Ingredient;

    /**
     * @param  list<Ulid>  $ids
     * @return array<string, Ingredient> keyed by the ingredient id value; unknown ids are omitted
     */
    public function findByIds(array $ids): array;

    public function findByNormalizedName(string $normalizedName): ?Ingredient;

    /**
     * Ranked by prefix match and trigram similarity against $term, most relevant first.
     *
     * @return list<Ingredient>
     */
    public function search(string $term, int $limit = 10): array;

    public function save(Ingredient $ingredient): void;
}
