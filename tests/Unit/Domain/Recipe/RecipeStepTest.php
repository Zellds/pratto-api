<?php

use App\Domain\Recipe\RecipeStep;

it('creates a valid step', function () {
    $step = RecipeStep::create(0, '  Pique a cebola.  ');

    expect($step->position())->toBe(0)
        ->and($step->instruction())->toBe('Pique a cebola.');
});

it('rejects an empty instruction', function () {
    RecipeStep::create(0, '   ');
})->throws(InvalidArgumentException::class);
