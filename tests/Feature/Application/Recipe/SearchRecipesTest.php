<?php

// tests/Feature/Application/Recipe/SearchRecipesTest.php

use App\Application\Rating\UseCases\RateRecipe;
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

it('includes the average rating for each recipe in the results', function () {
    $owner = anOwner();
    $draft = createADraft($owner->value());
    app(PublishRecipe::class)($draft->id, $owner->value());
    app(RateRecipe::class)($draft->id, anOwner()->value(), 3.0);

    $results = app(SearchRecipes::class)(null, null, 1, 20);

    $found = collect($results)->firstWhere('id', $draft->id);

    expect($found->averageRating)->toBe(3.0)
        ->and($found->ratingsCount)->toBe(1);
});
