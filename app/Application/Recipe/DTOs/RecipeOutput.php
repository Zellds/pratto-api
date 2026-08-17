<?php

// app/Application/Recipe/DTOs/RecipeOutput.php

namespace App\Application\Recipe\DTOs;

use App\Domain\Recipe\Recipe;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final readonly class RecipeOutput
{
    /**
     * @param  list<RecipeIngredientOutput>  $ingredients
     * @param  list<RecipeStepOutput>  $steps
     */
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $title,
        public string $description,
        public int $portions,
        public int $prepTimeMinutes,
        public string $status,
        public array $ingredients,
        public array $steps,
        public ?string $coverMediaId,
    ) {}

    public static function fromDomain(Recipe $recipe, ?int $requestedPortions = null): self
    {
        $ingredients = array_map(
            static fn ($ingredient) => new RecipeIngredientOutput(
                $ingredient->ingredientId()->value(),
                $ingredient->quantity(),
                $ingredient->unit()->value,
                $ingredient->position(),
            ),
            $recipe->scaledIngredients($requestedPortions),
        );

        $steps = array_map(
            static fn ($step) => new RecipeStepOutput($step->position(), $step->instruction()),
            $recipe->steps(),
        );

        return new self(
            $recipe->id()->value(),
            $recipe->ownerId()->value(),
            $recipe->title(),
            $recipe->description(),
            $recipe->portions(),
            $recipe->prepTimeMinutes(),
            $recipe->status()->value,
            $ingredients,
            $steps,
            $recipe->coverMediaId()?->value(),
        );
    }
}
