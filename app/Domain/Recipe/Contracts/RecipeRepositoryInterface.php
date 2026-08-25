<?php

namespace App\Domain\Recipe\Contracts;

use App\Domain\Recipe\Recipe;
use App\Domain\Shared\Ulid;

interface RecipeRepositoryInterface
{
    public function findById(Ulid $id): ?Recipe;

    public function save(Recipe $recipe): void;

    public function delete(Ulid $id): void;

    /**
     * Full-text search combined with a status filter. When $ownerId is given,
     * returns every recipe owned by that user regardless of status (used for
     * "my recipes", including drafts). When $ownerId is null, returns only
     * recipes with status pending_review/published (public discovery).
     *
     * @return list<Recipe>
     */
    public function search(?string $term, ?Ulid $ownerId, int $page, int $perPage): array;

    /**
     * Recipes owned by any of the given users, restricted to
     * pending_review/published (same public-discovery rule as search()
     * without an owner) — used by the follow feed, which never shows a
     * followed user's drafts or rejected recipes.
     *
     * @param  list<Ulid>  $ownerIds
     * @return list<Recipe>
     */
    public function forOwners(array $ownerIds, int $page, int $perPage): array;
}
