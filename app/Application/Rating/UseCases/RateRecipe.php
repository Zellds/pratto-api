<?php

namespace App\Application\Rating\UseCases;

use App\Application\Rating\DTOs\RatingOutput;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Rating\Rating;
use App\Domain\Rating\Score;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class RateRecipe
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private RatingRepositoryInterface $ratings,
    ) {}

    public function __invoke(string $recipeId, string $userId, float $scoreValue): RatingOutput
    {
        $recipeUlid = Ulid::fromString($recipeId);
        $userUlid = Ulid::fromString($userId);

        $recipe = $this->recipes->findById($recipeUlid);

        if ($recipe === null || ! $recipe->isVisibleTo($userUlid)) {
            throw RecipeNotFoundException::forId($recipeUlid);
        }

        $score = Score::create($scoreValue);
        $existing = $this->ratings->findByRecipeAndUser($recipeUlid, $userUlid);

        if ($existing !== null) {
            $existing->changeScore($score);
            $rating = $existing;
        } else {
            $rating = Rating::create(Ulid::generate(), $recipeUlid, $userUlid, $score);
        }

        $this->ratings->save($rating);

        return new RatingOutput(
            $rating->id()->value(),
            $rating->recipeId()->value(),
            $rating->userId()->value(),
            $rating->score()->value(),
        );
    }
}
