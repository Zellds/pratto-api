<?php

// tests/Feature/Application/Pantry/EloquentPantryItemRepositoryTest.php

use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Pantry;
use App\Domain\Pantry\PantryItem;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves an item and finds it back', function () {
    $repository = app(PantryItemRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);

    $item = PantryItem::create(Ulid::generate(), $pantry->id(), anIngredientId(), 3.0, MeasurementUnit::Whole, false);
    $repository->save($item);

    $found = $repository->findById($item->id());

    expect($found)->not->toBeNull()
        ->and($found->quantity())->toBe(3.0)
        ->and($found->needsToBuy())->toBeTrue();
});

it('finds an item by pantry and ingredient', function () {
    $repository = app(PantryItemRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $ingredientId = anIngredientId();

    $item = PantryItem::create(Ulid::generate(), $pantry->id(), $ingredientId, 1.0, MeasurementUnit::Whole, false);
    $repository->save($item);

    $found = $repository->findByPantryAndIngredient($pantry->id(), $ingredientId);

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($item->id()))->toBeTrue();
});

it('returns null when no item exists for that pantry and ingredient', function () {
    $repository = app(PantryItemRepositoryInterface::class);

    expect($repository->findByPantryAndIngredient(Ulid::generate(), Ulid::generate()))->toBeNull();
});

it('deletes an item', function () {
    $repository = app(PantryItemRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $item = PantryItem::create(Ulid::generate(), $pantry->id(), anIngredientId(), 1.0, MeasurementUnit::Whole, false);
    $repository->save($item);

    $repository->delete($item->id());

    expect($repository->findById($item->id()))->toBeNull();
});

it('lists every item for a pantry', function () {
    $repository = app(PantryItemRepositoryInterface::class);
    $pantry = Pantry::create(Ulid::generate(), anOwner(), 'Minha despensa');
    app(PantryRepositoryInterface::class)->save($pantry);
    $repository->save(PantryItem::create(Ulid::generate(), $pantry->id(), anIngredientId('Cebola'), 2.0, MeasurementUnit::Whole, false));
    $repository->save(PantryItem::create(Ulid::generate(), $pantry->id(), anIngredientId('Alho'), 1.0, MeasurementUnit::Whole, true));

    expect($repository->forPantry($pantry->id()))->toHaveCount(2);
});
