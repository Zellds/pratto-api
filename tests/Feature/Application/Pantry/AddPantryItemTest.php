<?php

// tests/Feature/Application/Pantry/AddPantryItemTest.php

use App\Application\Pantry\UseCases\AddPantryItem;
use App\Application\Pantry\UseCases\UpdatePantryItem;
use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Exceptions\PantryNotFoundException;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds a new item by ingredient name', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());

    $output = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 3.0, MeasurementUnit::Whole, false);

    expect($output->quantity)->toBe(3.0)
        ->and($output->unit)->toBe('unidade')
        ->and($output->needsToBuy)->toBeTrue();
});

it('upserts instead of duplicating when the ingredient is already in the pantry', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 3.0, MeasurementUnit::Whole, false);

    $output = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 5.0, MeasurementUnit::Kilogram, false);

    expect($output->quantity)->toBe(5.0)
        ->and($output->unit)->toBe('kg')
        ->and(app(PantryItemRepositoryInterface::class)->forPantry(Ulid::fromString($pantry->id)))
        ->toHaveCount(1);
});

it('re-marks needsToBuy=true when re-adding an item that was already in stock', function () {
    $owner = anOwner();
    $pantry = aPantry($owner->value());
    $added = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);
    app(UpdatePantryItem::class)($added->id, $owner->value(), false, null, null, null);

    $output = app(AddPantryItem::class)($pantry->id, $owner->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);

    expect($output->needsToBuy)->toBeTrue();
});

it('throws when the actor has no access to the pantry', function () {
    $pantry = aPantry(anOwner()->value());

    app(AddPantryItem::class)($pantry->id, anOwner()->value(), null, 'Cebola', 1.0, MeasurementUnit::Whole, false);
})->throws(PantryNotFoundException::class);
