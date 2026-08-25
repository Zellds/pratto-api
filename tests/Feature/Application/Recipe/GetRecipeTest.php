<?php

// tests/Feature/Application/Recipe/GetRecipeTest.php

use App\Application\Rating\UseCases\RateRecipe;
use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\GetRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scales ingredient quantities when portions is requested', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 4, 60,
        [new RecipeIngredientInput(null, 'Farinha', 2.0, 'g', 0)],
        [new RecipeStepInput(0, 'x')],
    ));
    app(PublishRecipe::class)($recipe->id, $owner->id);

    $output = app(GetRecipe::class)($recipe->id, null, 8);

    expect($output->ingredients[0]->quantity)->toBe(4.0);
});

it('hides a draft recipe from anyone but the owner', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $other = app(RegisterUser::class)(new RegisterUserInput('outra', 'Outra', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 4, 60,
        [new RecipeIngredientInput(null, 'Farinha', 2.0, 'g', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    app(GetRecipe::class)($recipe->id, $other->id, null);
})->throws(RecipeNotFoundException::class);

it('lets the owner see their own draft', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 4, 60,
        [new RecipeIngredientInput(null, 'Farinha', 2.0, 'g', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    $output = app(GetRecipe::class)($recipe->id, $owner->id, null);

    expect($output->status)->toBe('draft');
});

it('includes the average rating and count when there are ratings', function () {
    $owner = anOwner();
    $draft = createADraft($owner->value());
    app(PublishRecipe::class)($draft->id, $owner->value());

    app(RateRecipe::class)($draft->id, anOwner()->value(), 4.0);
    app(RateRecipe::class)($draft->id, anOwner()->value(), 5.0);

    $output = app(GetRecipe::class)($draft->id, null, null);

    expect($output->averageRating)->toBe(4.5)
        ->and($output->ratingsCount)->toBe(2);
});

it('returns a null average and zero count when there are no ratings', function () {
    $owner = anOwner();
    $draft = createADraft($owner->value());
    app(PublishRecipe::class)($draft->id, $owner->value());

    $output = app(GetRecipe::class)($draft->id, null, null);

    expect($output->averageRating)->toBeNull()
        ->and($output->ratingsCount)->toBe(0);
});
