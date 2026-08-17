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
