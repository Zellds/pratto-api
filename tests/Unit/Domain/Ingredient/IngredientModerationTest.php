<?php

use App\Domain\Ingredient\Enums\IngredientStatus;
use App\Domain\Ingredient\Ingredient;
use App\Domain\Ingredient\IngredientName;
use App\Domain\Shared\Ulid;

it('approves a provisional ingredient, returning a new instance', function () {
    $ingredient = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Cebola'));

    $approved = $ingredient->approve();

    expect($approved->status())->toBe(IngredientStatus::Approved)
        ->and($approved->id()->equals($ingredient->id()))->toBeTrue()
        ->and($ingredient->status())->toBe(IngredientStatus::Provisional);
});

it('rejects a provisional ingredient, returning a new instance', function () {
    $ingredient = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Xyz123'));

    $rejected = $ingredient->reject();

    expect($rejected->status())->toBe(IngredientStatus::Rejected)
        ->and($rejected->id()->equals($ingredient->id()))->toBeTrue();
});
