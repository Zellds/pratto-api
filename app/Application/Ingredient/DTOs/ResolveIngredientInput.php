<?php

namespace App\Application\Ingredient\DTOs;

final readonly class ResolveIngredientInput
{
    public function __construct(
        public ?string $ingredientId,
        public ?string $ingredientName,
    ) {}
}
