<?php

// tests/Feature/Application/Pantry/ListPantryItemsTest.php

use App\Application\Pantry\UseCases\AddPantryItem;
use App\Application\Pantry\UseCases\ListPantryItems;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists every item in a pantry', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 2.0, MeasurementUnit::Whole, false);
    app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Alho', 1.0, MeasurementUnit::Whole, true);

    $results = app(ListPantryItems::class)($pantry->id, $owner->value());

    expect($results)->toHaveCount(2);
});

it('throws when the actor has no access', function () {
    $pantry = aPantry(anOwner()->value());

    app(ListPantryItems::class)($pantry->id, anOwner()->value());
})->throws(PantryNotFoundException::class);
