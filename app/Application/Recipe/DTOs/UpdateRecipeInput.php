<?php

// app/Application/Recipe/DTOs/UpdateRecipeInput.php

namespace App\Application\Recipe\DTOs;

final readonly class UpdateRecipeInput
{
    /**
     * @param  list<RecipeIngredientInput>  $ingredients
     * @param  list<RecipeStepInput>  $steps
     */
    public function __construct(
        public string $recipeId,
        public string $requesterId,
        public string $title,
        public string $description,
        public int $portions,
        public int $prepTimeMinutes,
        public array $ingredients,
        public array $steps,
        public ?string $coverMediaId = null,
    ) {}
}
