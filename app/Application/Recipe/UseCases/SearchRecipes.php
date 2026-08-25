<?php

// app/Application/Recipe/UseCases/SearchRecipes.php

namespace App\Application\Recipe\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;

final readonly class SearchRecipes
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private RatingRepositoryInterface $ratings,
    ) {}

    /**
     * @return list<RecipeOutput>
     */
    public function __invoke(?string $term, ?string $mineOwnerId, int $page, int $perPage): array
    {
        $ownerId = $mineOwnerId === null ? null : Ulid::fromString($mineOwnerId);
        $recipes = $this->recipes->search($term, $ownerId, $page, $perPage);

        $ids = array_map(static fn ($recipe) => $recipe->id(), $recipes);
        $aggregates = $this->ratings->averagesAndCountsFor($ids);

        return array_map(function ($recipe) use ($aggregates) {
            $aggregate = $aggregates[$recipe->id()->value()] ?? ['average' => null, 'count' => 0];

            return RecipeOutput::fromDomain($recipe)
                ->withRatingAggregate($aggregate['average'], $aggregate['count']);
        }, $recipes);
    }
}
