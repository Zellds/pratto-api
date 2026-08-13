<?php

// tests/Feature/Application/Recipe/UpdateRecipeTest.php

use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\DTOs\UpdateRecipeInput;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\Recipe\UseCases\UpdateRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\RecipeNotOwnedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates the recipe and resets a pending_review recipe back to draft', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $recipe = createADraft($owner->id);
    app(PublishRecipe::class)($recipe->id, $owner->id);

    $updated = app(UpdateRecipe::class)(new UpdateRecipeInput(
        $recipe->id, $owner->id, 'Bolo atualizado', 'Nova descrição', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 2.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Novo passo.')],
    ));

    expect($updated->title)->toBe('Bolo atualizado')
        ->and($updated->status)->toBe('draft');
});

it('rejects an update from someone who is not the owner', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));
    $other = app(RegisterUser::class)(new RegisterUserInput('outra', 'Outra', 'senha-forte-123'));
    $recipe = createADraft($owner->id);

    app(UpdateRecipe::class)(new UpdateRecipeInput(
        $recipe->id, $other->id, 'x', 'x', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'x')],
    ));
})->throws(RecipeNotOwnedException::class);
