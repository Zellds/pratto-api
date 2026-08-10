<?php
// app/Application/Recipe/UseCases/CreateRecipe.php

namespace App\Application\Recipe\UseCases;

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeOutput;
use App\Domain\Recipe\MeasurementUnit;
use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;

final readonly class CreateRecipe
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private ResolveIngredient $resolveIngredient,
    ) {}

    public function __invoke(CreateRecipeInput $input): RecipeOutput
    {
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

        $recipe = Recipe::create(
            Ulid::generate(),
            Ulid::fromString($input->ownerId),
            $input->title,
            $input->description,
            $input->portions,
            $input->prepTimeMinutes,
            $ingredients,
            $steps,
        );

        $this->recipes->save($recipe);

        return RecipeOutput::fromDomain($recipe);
    }
}
