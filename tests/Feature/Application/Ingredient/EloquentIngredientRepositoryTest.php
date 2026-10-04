<?php

use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Ingredient\Enums\IngredientStatus;
use App\Domain\Ingredient\Ingredient;
use App\Domain\Ingredient\IngredientName;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves an ingredient and finds it back by id and by normalized name', function () {
    $repository = app(IngredientRepositoryInterface::class);
    $ingredient = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Tomate'));

    $repository->save($ingredient);

    $byId = $repository->findById($ingredient->id());
    $byName = $repository->findByNormalizedName('tomate');

    expect($byId)->not->toBeNull()
        ->and($byId->name()->value())->toBe('Tomate')
        ->and($byId->status())->toBe(IngredientStatus::Provisional)
        ->and($byName)->not->toBeNull()
        ->and($byName->id()->equals($ingredient->id()))->toBeTrue();
});

it('returns null when nothing matches', function () {
    $repository = app(IngredientRepositoryInterface::class);

    expect($repository->findByNormalizedName('inexistente'))->toBeNull();
});

it('searches by prefix and by trigram similarity, ranked, most similar first', function () {
    $repository = app(IngredientRepositoryInterface::class);
    $repository->save(Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Tomate')));
    $repository->save(Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Tomate cereja')));
    $repository->save(Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Cebola')));

    $prefixResults = $repository->search('tomat');
    $typoResults = $repository->search('tomatee');

    expect(collect($prefixResults)->map(fn ($i) => $i->name()->value())->all())
        ->toEqualCanonicalizing(['Tomate', 'Tomate cereja'])
        ->and($typoResults)->not->toBeEmpty()
        ->and($typoResults[0]->name()->value())->toStartWith('Tomate');
});

it('finds several ingredients by id, keyed by the id value', function () {
    $repository = app(IngredientRepositoryInterface::class);
    $carrot = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Cenoura'));
    $onion = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Cebola'));
    $repository->save($carrot);
    $repository->save($onion);

    $found = $repository->findByIds([$carrot->id(), $onion->id()]);

    expect($found)->toHaveCount(2)
        ->and($found[$carrot->id()->value()]->name()->value())->toBe('Cenoura')
        ->and($found[$onion->id()->value()]->name()->value())->toBe('Cebola');
});

it('omits unknown ids when finding several ingredients by id', function () {
    $repository = app(IngredientRepositoryInterface::class);
    $carrot = Ingredient::createProvisional(Ulid::generate(), IngredientName::fromString('Cenoura'));
    $repository->save($carrot);

    $found = $repository->findByIds([$carrot->id(), Ulid::generate()]);

    expect($found)->toHaveCount(1)
        ->and(array_keys($found))->toBe([$carrot->id()->value()]);
});

it('returns an empty array when finding by an empty list of ids', function () {
    expect(app(IngredientRepositoryInterface::class)->findByIds([]))->toBe([]);
});
