<?php
// tests/Feature/Application/Recipe/DeleteRecipeTest.php

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\Recipe\UseCases\DeleteRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\RecipeNotFoundException;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('soft deletes the owned recipe', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $recipe = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id, 'Bolo', 'x', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'x')],
    ));

    app(DeleteRecipe::class)($recipe->id, $owner->id);

    expect(app(RecipeRepositoryInterface::class)->findById(Ulid::fromString($recipe->id)))->toBeNull();
});

it('fails when the recipe does not exist', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));

    app(DeleteRecipe::class)((string) \Symfony\Component\Uid\Ulid::generate(), $owner->id);
})->throws(RecipeNotFoundException::class);
