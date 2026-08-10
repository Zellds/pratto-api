<?php
// tests/Feature/Application/Recipe/PublishRecipeTest.php

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\RecipeNotOwnedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('moves a draft recipe to pending_review', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    $output = app(PublishRecipe::class)($recipe->id, $owner->id);

    expect($output->status)->toBe('pending_review');
});

it('rejects publishing from someone who is not the owner', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $other = app(RegisterUser::class)(new RegisterUserInput('outra', 'Outra', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    app(PublishRecipe::class)($recipe->id, $other->id);
})->throws(RecipeNotOwnedException::class);
