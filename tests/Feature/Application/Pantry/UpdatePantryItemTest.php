<?php

// tests/Feature/Application/Pantry/UpdatePantryItemTest.php

use App\Application\Pantry\UseCases\AddPantryItem;
use App\Application\Pantry\UseCases\UpdatePantryItem;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('toggles needsToBuy without touching quantity', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 3.0, MeasurementUnit::Whole, false);

    $output = app(UpdatePantryItem::class)($item->id, $owner->value(), false, null, null, null);

    expect($output->needsToBuy)->toBeFalse()
        ->and($output->quantity)->toBe(3.0);
});

it('updates quantity and unit together', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 3.0, MeasurementUnit::Whole, false);

    $output = app(UpdatePantryItem::class)($item->id, $owner->value(), null, 500.0, MeasurementUnit::Gram, null);

    expect($output->quantity)->toBe(500.0)
        ->and($output->unit)->toBe('g');
});

it('marks an item as fixed', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);

    $output = app(UpdatePantryItem::class)($item->id, $owner->value(), null, null, null, true);

    expect($output->isFixed)->toBeTrue();
});

it('throws when the actor has no access to the item\'s pantry', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $item = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);

    app(UpdatePantryItem::class)($item->id, anOwner()->value(), false, null, null, null);
})->throws(PantryNotFoundException::class);

it('throws when the item does not exist', function () {
    app(UpdatePantryItem::class)((string) new Ulid, anOwner()->value(), false, null, null, null);
})->throws(PantryNotFoundException::class);
