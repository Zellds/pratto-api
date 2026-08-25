<?php

// app/Application/Recipe/UseCases/GetRecipe.php

namespace App\Application\Recipe\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class GetRecipe
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private RatingRepositoryInterface $ratings,
    ) {}

    public function __invoke(string $recipeId, ?string $viewerId, ?int $requestedPortions): RecipeOutput
    {
        $recipe = $this->recipes->findById(Ulid::fromString($recipeId));
        $viewer = $viewerId === null ? null : Ulid::fromString($viewerId);

        if ($recipe === null || ! $recipe->isVisibleTo($viewer)) {
            throw RecipeNotFoundException::forId(Ulid::fromString($recipeId));
        }

        $aggregate = $this->ratings->averageAndCountFor($recipe->id());

        return RecipeOutput::fromDomain($recipe, $requestedPortions)
            ->withRatingAggregate($aggregate['average'], $aggregate['count']);
    }
}
