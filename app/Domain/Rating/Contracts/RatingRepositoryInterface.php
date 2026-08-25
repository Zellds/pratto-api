<?php

namespace App\Domain\Rating\Contracts;

use App\Domain\Rating\Rating;
use App\Domain\Shared\Ulid;

interface RatingRepositoryInterface
{
    public function findByRecipeAndUser(Ulid $recipeId, Ulid $userId): ?Rating;

    public function save(Rating $rating): void;

    /**
     * @return array{average: ?float, count: int}
     */
    public function averageAndCountFor(Ulid $recipeId): array;

    /**
     * @param  list<Ulid>  $recipeIds
     * @return array<string, array{average: float, count: int}> keyed by recipe id
     */
    public function averagesAndCountsFor(array $recipeIds): array;
}
