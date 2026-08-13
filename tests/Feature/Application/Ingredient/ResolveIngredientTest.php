<?php

use App\Application\Ingredient\DTOs\ResolveIngredientInput;
use App\Application\Ingredient\UseCases\ResolveIngredient;
use App\Domain\Ingredient\IngredientStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('creates a provisional ingredient when the name has no match', function () {
    $resolve = app(ResolveIngredient::class);

    $ingredient = $resolve(new ResolveIngredientInput(null, 'Tomate'));

    expect($ingredient->name()->value())->toBe('Tomate')
        ->and($ingredient->status())->toBe(IngredientStatus::Provisional);
});

it('reuses an existing ingredient on exact normalized match', function () {
    $resolve = app(ResolveIngredient::class);
    $first = $resolve(new ResolveIngredientInput(null, 'Tomate'));

    $second = $resolve(new ResolveIngredientInput(null, '  tomate  '));

    expect($second->id()->equals($first->id()))->toBeTrue();
});

it('uses the given ingredient id directly when present', function () {
    $resolve = app(ResolveIngredient::class);
    $created = $resolve(new ResolveIngredientInput(null, 'Tomate'));

    $found = $resolve(new ResolveIngredientInput($created->id()->value(), null));

    expect($found->id()->equals($created->id()))->toBeTrue();
});

it('fails when neither id nor name is given', function () {
    app(ResolveIngredient::class)(new ResolveIngredientInput(null, null));
})->throws(InvalidArgumentException::class);

it('fails when the given id does not exist', function () {
    app(ResolveIngredient::class)(new ResolveIngredientInput((string) Ulid::generate(), null));
})->throws(RuntimeException::class);
