<?php

// app/Application/Follow/UseCases/GetFeed.php

namespace App\Application\Follow\UseCases;

use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;

final readonly class GetFeed
{
    public function __construct(
        private FollowRepositoryInterface $follows,
        private RecipeRepositoryInterface $recipes,
        private RatingRepositoryInterface $ratings,
    ) {}

    /**
     * @return list<RecipeOutput>
     */
    public function __invoke(string $followerId, int $page, int $perPage): array
    {
        $followeeIds = $this->follows->followeeIdsFor(Ulid::fromString($followerId));

        if ($followeeIds === []) {
            return [];
        }

        $recipes = $this->recipes->forOwners($followeeIds, $page, $perPage);

        $ids = array_map(static fn ($recipe) => $recipe->id(), $recipes);
        $aggregates = $this->ratings->averagesAndCountsFor($ids);

        return array_map(function ($recipe) use ($aggregates) {
            $aggregate = $aggregates[$recipe->id()->value()] ?? ['average' => null, 'count' => 0];

            return RecipeOutput::fromDomain($recipe)
                ->withRatingAggregate($aggregate['average'], $aggregate['count']);
        }, $recipes);
    }
}
