<?php

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Application\Ingredient\UseCases\SearchIngredients;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns matching ingredients as output DTOs', function () {
    app(ResolveIngredient::class)(new ResolveIngredientInput(null, 'Tomate'));

    $results = app(SearchIngredients::class)('tomat');

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Tomate')
        ->and($results[0]->status)->toBe('provisional');
});

it('returns an empty array when nothing matches', function () {
    expect(app(SearchIngredients::class)('inexistente'))->toBe([]);
});
