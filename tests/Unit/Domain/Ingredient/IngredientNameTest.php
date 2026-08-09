<?php

use App\Domain\Ingredient\IngredientName;

it('trims and keeps the original casing as the display value', function () {
    $name = IngredientName::fromString('  Tomate Italiano  ');

    expect($name->value())->toBe('Tomate Italiano');
});

it('normalizes to lowercase without accents for matching', function () {
    $name = IngredientName::fromString('Pêssego');

    expect($name->normalized())->toBe('pessego');
});

it('collapses repeated whitespace when normalizing', function () {
    expect(IngredientName::normalize('Farinha   de   trigo'))->toBe('farinha de trigo');
});

it('rejects an empty name', function () {
    IngredientName::fromString('   ');
})->throws(InvalidArgumentException::class);

it('rejects a name longer than 80 characters', function () {
    IngredientName::fromString(str_repeat('a', 81));
})->throws(InvalidArgumentException::class);
