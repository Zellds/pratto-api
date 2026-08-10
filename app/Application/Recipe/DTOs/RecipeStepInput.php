<?php
// app/Application/Recipe/DTOs/RecipeStepInput.php

namespace App\Application\Recipe\DTOs;

final readonly class RecipeStepInput
{
    public function __construct(
        public int $position,
        public string $instruction,
    ) {}
}
