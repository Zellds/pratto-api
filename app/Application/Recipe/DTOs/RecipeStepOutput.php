<?php

// app/Application/Recipe/DTOs/RecipeStepOutput.php

namespace App\Application\Recipe\DTOs;

final readonly class RecipeStepOutput
{
    public function __construct(
        public int $position,
        public string $instruction,
    ) {}
}
