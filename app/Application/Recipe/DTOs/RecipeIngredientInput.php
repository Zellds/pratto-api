<?php
// app/Application/Recipe/DTOs/RecipeIngredientInput.php

namespace App\Application\Recipe\DTOs;

final readonly class RecipeIngredientInput
{
    public function __construct(
        public ?string $ingredientId,
        public ?string $ingredientName,
        public float $quantity,
        public string $unit,
        public int $position,
    ) {}
}
