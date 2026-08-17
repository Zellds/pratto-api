<?php

// tests/Feature/Application/Recipe/CreateRecipeTest.php

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
use App\Domain\Recipe\Exceptions\CoverMediaNotOwnedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a draft recipe, resolving a brand-new ingredient by name', function () {
    $owner = app(RegisterUser::class)(new RegisterUserInput('gabriel', 'Gabriel', 'senha-forte-123'));

    $output = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->id,
        'Bolo de cenoura',
        'Bolo simples e rápido',
        8,
        60,
        [new RecipeIngredientInput(null, 'Cenoura', 3.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Bata tudo no liquidificador.')],
    ));

    expect($output->status)->toBe('draft')
        ->and($output->title)->toBe('Bolo de cenoura')
        ->and($output->ingredients)->toHaveCount(1)
        ->and($output->ingredients[0]->quantity)->toBe(3.0);
});

it('accepts a cover media owned by the same user', function () {
    $owner = anOwner();
    $cover = aPendingRecipePhoto($owner->value());

    $output = app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->value(), 'Bolo', 'Descrição', 4, 30,
        [new RecipeIngredientInput(null, 'Farinha', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Misture.')],
        $cover->id,
    ));

    expect($output->coverMediaId)->toBe($cover->id);
});

it('rejects a cover media owned by someone else', function () {
    $owner = anOwner();
    $strangerCover = aPendingRecipePhoto(anOwner()->value());

    app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->value(), 'Bolo', 'Descrição', 4, 30,
        [new RecipeIngredientInput(null, 'Farinha', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Misture.')],
        $strangerCover->id,
    ));
})->throws(CoverMediaNotOwnedException::class);

it('rejects an avatar-kind media used as a cover', function () {
    $owner = anOwner();
    $avatar = anApprovedAvatar($owner->value());

    app(CreateRecipe::class)(new CreateRecipeInput(
        $owner->value(), 'Bolo', 'Descrição', 4, 30,
        [new RecipeIngredientInput(null, 'Farinha', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Misture.')],
        $avatar->id,
    ));
})->throws(CoverMediaNotOwnedException::class);
