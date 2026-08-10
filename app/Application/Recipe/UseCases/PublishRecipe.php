<?php
// app/Application/Recipe/UseCases/PublishRecipe.php

namespace App\Application\Recipe\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Recipe\RecipeNotFoundException;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;

final readonly class PublishRecipe
{
    public function __construct(private RecipeRepositoryInterface $recipes) {}

    public function __invoke(string $recipeId, string $requesterId): RecipeOutput
    {
        $recipe = $this->recipes->findById(Ulid::fromString($recipeId));

        if ($recipe === null) {
            throw RecipeNotFoundException::forId(Ulid::fromString($recipeId));
        }

        $recipe->assertOwnedBy(Ulid::fromString($requesterId));
        $recipe->publish();

        $this->recipes->save($recipe);

        return RecipeOutput::fromDomain($recipe);
    }
}
