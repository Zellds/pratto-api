<?php

// tests/Feature/Application/Recipe/CreateRecipeTest.php

use App\Application\Recipe\DTOs\CreateRecipeInput;
use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\UseCases\CreateRecipe;
use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\UseCases\RegisterUser;
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
