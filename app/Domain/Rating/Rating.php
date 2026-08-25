<?php

namespace App\Domain\Rating;

use App\Domain\Shared\Ulid;

/**
 * Aggregate root for a single user's rating of a recipe: one per
 * (recipe, user) pair, always upserted rather than deleted.
 */
final class Rating
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $recipeId,
        private readonly Ulid $userId,
        private Score $score,
    ) {}

    public static function create(Ulid $id, Ulid $recipeId, Ulid $userId, Score $score): self
    {
        return new self($id, $recipeId, $userId, $score);
    }

    public static function reconstitute(Ulid $id, Ulid $recipeId, Ulid $userId, Score $score): self
    {
        return new self($id, $recipeId, $userId, $score);
    }

    public function changeScore(Score $score): void
    {
        $this->score = $score;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function recipeId(): Ulid
    {
        return $this->recipeId;
    }

    public function userId(): Ulid
    {
        return $this->userId;
    }

    public function score(): Score
    {
        return $this->score;
    }
}
