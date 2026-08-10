<?php
// app/Application/Recipe/UseCases/DeleteRecipe.php

namespace App\Application\Recipe\UseCases;

use App\Domain\Recipe\RecipeNotFoundException;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;

final readonly class DeleteRecipe
{
    public function __construct(private RecipeRepositoryInterface $recipes) {}

    public function __invoke(string $recipeId, string $requesterId): void
    {
        $recipe = $this->recipes->findById(Ulid::fromString($recipeId));

        if ($recipe === null) {
            throw RecipeNotFoundException::forId(Ulid::fromString($recipeId));
        }

        $recipe->assertOwnedBy(Ulid::fromString($requesterId));

        $this->recipes->delete($recipe->id());
    }
}
