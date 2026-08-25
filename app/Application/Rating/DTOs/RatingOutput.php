<?php

namespace App\Application\Rating\DTOs;

final readonly class RatingOutput
{
    public function __construct(
        public string $id,
        public string $recipeId,
        public string $userId,
        public float $score,
    ) {}
}
