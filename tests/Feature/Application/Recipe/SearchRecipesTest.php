<?php
// tests/Feature/Application/Recipe/SearchRecipesTest.php

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\Recipe\UseCases\SearchRecipes;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('the "mine" filter returns drafts that a public search would hide', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 4, 60,
        [new RecipeIngredientInput(null, 'Farinha', 2.0, 'g', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    expect(app(SearchRecipes::class)(null, null, 1, 20))->toBeEmpty()
        ->and(app(SearchRecipes::class)(null, $owner->id, 1, 20))->toHaveCount(1);
});
