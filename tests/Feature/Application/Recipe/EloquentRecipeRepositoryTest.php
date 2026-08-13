<?php

use App\Domain\Recipe\MeasurementUnit;
use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeRepositoryInterface;
use App\Domain\Recipe\RecipeStatus;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a recipe with its ingredients and steps and finds it back, ordered by position', function () {
    $repository = app(RecipeRepositoryInterface::class);
    $owner = anOwner();
    $ingredientId = anIngredientId();

    $recipe = Recipe::create(
        Ulid::generate(),
        $owner,
        'Bolo',
        'Bolo simples',
        8,
        60,
        [
            RecipeIngredient::create($ingredientId, 2.0, MeasurementUnit::Cup, 1),
            RecipeIngredient::create($ingredientId, 1.0, MeasurementUnit::Gram, 0),
        ],
        [
            RecipeStep::create(1, 'Asse por 40 minutos.'),
            RecipeStep::create(0, 'Misture os ingredientes.'),
        ],
    );

    $repository->save($recipe);
    $found = $repository->findById($recipe->id());

    expect($found)->not->toBeNull()
        ->and($found->ingredients())->toHaveCount(2)
        ->and($found->ingredients()[0]->position())->toBe(0)
        ->and($found->steps()[0]->instruction())->toBe('Misture os ingredientes.');
});

it('replaces ingredients and steps on a second save', function () {
    $repository = app(RecipeRepositoryInterface::class);
    $owner = anOwner();
    $ingredientId = anIngredientId();

    $recipe = Recipe::create(
        Ulid::generate(),
        $owner,
        'Bolo',
        'Bolo simples',
        8,
        60,
        [RecipeIngredient::create($ingredientId, 2.0, MeasurementUnit::Cup, 0)],
        [RecipeStep::create(0, 'Misture.')],
    );
    $repository->save($recipe);

    $recipe->update('Bolo', 'Bolo simples', 8, 60, [
        RecipeIngredient::create($ingredientId, 5.0, MeasurementUnit::Gram, 0),
    ], [RecipeStep::create(0, 'Novo passo.')]);
    $repository->save($recipe);

    $found = $repository->findById($recipe->id());

    expect($found->ingredients())->toHaveCount(1)
        ->and($found->ingredients()[0]->quantity())->toBe(5.0)
        ->and($found->steps())->toHaveCount(1)
        ->and($found->steps()[0]->instruction())->toBe('Novo passo.');
});

it('soft deletes: findById returns null after delete', function () {
    $repository = app(RecipeRepositoryInterface::class);
    $recipe = Recipe::create(
        Ulid::generate(), anOwner(), 'Bolo', 'Bolo simples', 8, 60,
        [RecipeIngredient::create(anIngredientId(), 1.0, MeasurementUnit::Gram, 0)],
        [RecipeStep::create(0, 'Misture.')],
    );
    $repository->save($recipe);

    $repository->delete($recipe->id());

    expect($repository->findById($recipe->id()))->toBeNull();
});

it('search without an owner only returns pending_review/published recipes, matched by title, description or ingredient name', function () {
    $repository = app(RecipeRepositoryInterface::class);
    $owner = anOwner();
    $ingredientId = anIngredientId('Chocolate');

    $draft = Recipe::create(Ulid::generate(), $owner, 'Bolo de chocolate', 'x', 8, 60, [RecipeIngredient::create($ingredientId, 1.0, MeasurementUnit::Gram, 0)], [RecipeStep::create(0, 'x')]);
    $repository->save($draft);

    $published = Recipe::create(Ulid::generate(), $owner, 'Torta salgada', 'Sem chocolate', 8, 60, [RecipeIngredient::create(anIngredientId('Farinha'), 1.0, MeasurementUnit::Gram, 0)], [RecipeStep::create(0, 'x')]);
    $published->publish();
    $repository->save($published);

    $results = $repository->search('chocolate', null, 1, 10);

    expect(collect($results)->map(fn (Recipe $r) => $r->title())->all())->toBe(['Torta salgada']);
});

it('search with an owner returns every status owned by that user, including drafts', function () {
    $repository = app(RecipeRepositoryInterface::class);
    $owner = anOwner();
    $recipe = Recipe::create(Ulid::generate(), $owner, 'Bolo', 'x', 8, 60, [RecipeIngredient::create(anIngredientId(), 1.0, MeasurementUnit::Gram, 0)], [RecipeStep::create(0, 'x')]);
    $repository->save($recipe);

    $results = $repository->search(null, $owner, 1, 10);

    expect($results)->toHaveCount(1)
        ->and($results[0]->status())->toBe(RecipeStatus::Draft);
});
