<?php
// app/Application/Recipe/DTOs/RecipeIngredientOutput.php

namespace App\Application\Recipe\DTOs;

final readonly class RecipeIngredientOutput
{
    public function __construct(
        public string $ingredientId,
        public float $quantity,
        public string $unit,
        public int $position,
    ) {}
}
