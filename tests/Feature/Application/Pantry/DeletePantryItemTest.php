<?php

// tests/Feature/Application/Pantry/DeletePantryItemTest.php

use App\Application\Pantry\UseCases\AddPantryItem;
use App\Application\Pantry\UseCases\DeletePantryItem;
use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes an item', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);

    app(DeletePantryItem::class)($item->id, $owner->value());

    expect(app(PantryItemRepositoryInterface::class)->findById(Ulid::fromString($item->id)))->toBeNull();
});

it('throws when the actor has no access to the item\'s pantry', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);

    app(DeletePantryItem::class)($item->id, anOwner()->value());
})->throws(PantryNotFoundException::class);
