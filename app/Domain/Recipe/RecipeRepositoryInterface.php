<?php

namespace App\Domain\Recipe;

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
}
