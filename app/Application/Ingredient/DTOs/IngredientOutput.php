<?php

namespace App\Application\Ingredient\DTOs;

final readonly class IngredientOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
    ) {}
}
