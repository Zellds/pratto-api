<?php

namespace App\Application\Recipe\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ApproveRecipe
{
    public function __construct(private RecipeRepositoryInterface $recipes) {}

    public function __invoke(string $recipeId, string $reviewerId): RecipeOutput
    {
        $id = Ulid::fromString($recipeId);
        $recipe = $this->recipes->findById($id);

        if ($recipe === null) {
            throw RecipeNotFoundException::forId($id);
        }

        $recipe->approve(Ulid::fromString($reviewerId));
        $this->recipes->save($recipe);

        return RecipeOutput::fromDomain($recipe);
    }
}
