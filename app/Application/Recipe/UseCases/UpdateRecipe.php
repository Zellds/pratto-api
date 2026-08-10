<?php
// app/Application/Recipe/UseCases/UpdateRecipe.php

namespace App\Application\Recipe\UseCases;

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Application\Recipe\DTOs\RecipeOutput;
use App\Application\Recipe\DTOs\UpdateRecipeInput;
use App\Domain\Recipe\MeasurementUnit;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeNotFoundException;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;

final readonly class UpdateRecipe
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private ResolveIngredient $resolveIngredient,
    ) {}

    public function __invoke(UpdateRecipeInput $input): RecipeOutput
    {
        $recipe = $this->recipes->findById(Ulid::fromString($input->recipeId));

        if ($recipe === null) {
            throw RecipeNotFoundException::forId(Ulid::fromString($input->recipeId));
        }

        $recipe->assertOwnedBy(Ulid::fromString($input->requesterId));

        $ingredients = array_map(
            fn ($line) => RecipeIngredient::create(
                ($this->resolveIngredient)(new ResolveIngredientInput($line->ingredientId, $line->ingredientName))->id(),
                $line->quantity,
                MeasurementUnit::from($line->unit),
                $line->position,
            ),
            $input->ingredients,
        );

        $steps = array_map(
            static fn ($step) => RecipeStep::create($step->position, $step->instruction),
            $input->steps,
        );

        $recipe->update($input->title, $input->description, $input->portions, $input->prepTimeMinutes, $ingredients, $steps);

        $this->recipes->save($recipe);

        return RecipeOutput::fromDomain($recipe);
    }
}
