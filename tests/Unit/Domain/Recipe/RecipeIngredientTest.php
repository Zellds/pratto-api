<?php

use App\Domain\Recipe\MeasurementUnit;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Shared\Ulid;

it('creates a valid recipe ingredient line', function () {
    $line = RecipeIngredient::create(Ulid::generate(), 2.5, MeasurementUnit::Cup, 0);

    expect($line->quantity())->toBe(2.5)
        ->and($line->unit())->toBe(MeasurementUnit::Cup)
        ->and($line->position())->toBe(0);
});

it('rejects a non-positive quantity', function () {
    RecipeIngredient::create(Ulid::generate(), 0.0, MeasurementUnit::Gram, 0);
})->throws(InvalidArgumentException::class);

it('scales the quantity by a ratio, keeping everything else', function () {
    $line = RecipeIngredient::create(Ulid::generate(), 2.0, MeasurementUnit::Gram, 3);

    $scaled = $line->scaledBy(1.5);

    expect($scaled->quantity())->toBe(3.0)
        ->and($scaled->position())->toBe(3)
        ->and($scaled->ingredientId()->equals($line->ingredientId()))->toBeTrue();
});
