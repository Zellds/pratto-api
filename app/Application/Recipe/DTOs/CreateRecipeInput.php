<?php

// app/Application/Recipe/DTOs/CreateRecipeInput.php

namespace App\Application\Recipe\DTOs;

final readonly class CreateRecipeInput
{
    /**
     * @param  list<RecipeIngredientInput>  $ingredients
     * @param  list<RecipeStepInput>  $steps
     */
    public function __construct(
        public string $ownerId,
        public string $title,
        public string $description,
        public int $portions,
        public int $prepTimeMinutes,
        public array $ingredients,
        public array $steps,
    ) {}
}
