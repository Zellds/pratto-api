<?php

use App\Domain\Ingredient\Ingredient;
use App\Domain\Ingredient\IngredientName;
use App\Domain\Ingredient\IngredientStatus;
use App\Domain\Shared\Ulid;

it('is created as provisional', function () {
    $ingredient = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Tomate'));

    expect($ingredient->status())->toBe(IngredientStatus::Provisional)
        ->and($ingredient->name()->value())->toBe('Tomate');
});

it('can be reconstituted with an explicit status', function () {
    $id = Ulid::generate();
    $ingredient = Ingredient::reconstitute($id, IngredientName::fromString('Tomate'), IngredientStatus::Approved);

    expect($ingredient->id()->equals($id))->toBeTrue()
        ->and($ingredient->status())->toBe(IngredientStatus::Approved);
});
