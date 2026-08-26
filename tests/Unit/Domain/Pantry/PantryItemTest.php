<?php

use App\Domain\Pantry\PantryItem;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;

it('creates an item that needs to be bought by default', function () {
    $item = PantryItem::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), 3.0, MeasurementUnit::Whole, false);

    expect($item->quantity())->toBe(3.0)
        ->and($item->unit())->toBe(MeasurementUnit::Whole)
        ->and($item->needsToBuy())->toBeTrue()
        ->and($item->isFixed())->toBeFalse();
});

it('rejects a zero or negative quantity', function () {
    PantryItem::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), 0.0, MeasurementUnit::Whole, false);
})->throws(InvalidArgumentException::class);

it('toggles needsToBuy', function () {
    $item = PantryItem::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), 1.0, MeasurementUnit::Whole, false);

    $item->toggleNeedsToBuy(false);

    expect($item->needsToBuy())->toBeFalse();
});

it('updates quantity and unit', function () {
    $item = PantryItem::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), 1.0, MeasurementUnit::Whole, false);

    $item->updateQuantity(500.0, MeasurementUnit::Gram);

    expect($item->quantity())->toBe(500.0)
        ->and($item->unit())->toBe(MeasurementUnit::Gram);
});

it('marks an item as fixed', function () {
    $item = PantryItem::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), 1.0, MeasurementUnit::Whole, false);

    $item->markFixed(true);

    expect($item->isFixed())->toBeTrue();
});

it('reconstitutes with the same field order as create plus needsToBuy', function () {
    $id = Ulid::generate();
    $pantryId = Ulid::generate();
    $ingredientId = Ulid::generate();

    $item = PantryItem::reconstitute($id, $pantryId, $ingredientId, 2.0, MeasurementUnit::Cup, false, true);

    expect($item->id()->equals($id))->toBeTrue()
        ->and($item->pantryId()->equals($pantryId))->toBeTrue()
        ->and($item->ingredientId()->equals($ingredientId))->toBeTrue()
        ->and($item->needsToBuy())->toBeFalse()
        ->and($item->isFixed())->toBeTrue();
});
