<?php

use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Recipe\Enums\RecipeStatus;
use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;

function aPendingRecipeForModeration(): Recipe
{
    $recipe = Recipe::create(
        Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60,
        [RecipeIngredient::create(Ulid::generate(), 1.0, MeasurementUnit::Gram, 0)],
        [RecipeStep::create(0, 'Misture.')],
    );
    $recipe->publish();

    return $recipe;
}

it('approves a recipe, publishing it and clearing any rejection reason', function () {
    $reviewer = Ulid::generate();
    $recipe = aPendingRecipeForModeration();

    $recipe->approve($reviewer);

    expect($recipe->status())->toBe(RecipeStatus::Published)
        ->and($recipe->rejectionReason())->toBeNull()
        ->and($recipe->reviewedBy()->equals($reviewer))->toBeTrue()
        ->and($recipe->reviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('approves a recipe regardless of its current status, including already published', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->approve(Ulid::generate());

    $recipe->approve(Ulid::generate());

    expect($recipe->status())->toBe(RecipeStatus::Published);
});

it('rejects a recipe with a reason and marks it permanently as ever-rejected', function () {
    $reviewer = Ulid::generate();
    $recipe = aPendingRecipeForModeration();

    $recipe->reject($reviewer, 'Foto imprópria.');

    expect($recipe->status())->toBe(RecipeStatus::Rejected)
        ->and($recipe->rejectionReason())->toBe('Foto imprópria.')
        ->and($recipe->wasEverRejected())->toBeTrue()
        ->and($recipe->reviewedBy()->equals($reviewer))->toBeTrue();
});

it('rejects an already-published recipe too, taking it down after the fact', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->approve(Ulid::generate());

    $recipe->reject(Ulid::generate(), 'Denunciado depois de publicado.');

    expect($recipe->status())->toBe(RecipeStatus::Rejected);
});

it('rejects an empty rejection reason', function () {
    $recipe = aPendingRecipeForModeration();

    $recipe->reject(Ulid::generate(), '   ');
})->throws(InvalidArgumentException::class);

it('never resets wasEverRejected, even after a later approval', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->reject(Ulid::generate(), 'Primeira rejeição.');

    $recipe->approve(Ulid::generate());

    expect($recipe->wasEverRejected())->toBeTrue();
});

it('is always visible to the owner regardless of status', function () {
    $owner = Ulid::generate();
    $recipe = Recipe::create(
        Ulid::generate(), $owner, 'Bolo', 'x', 8, 60,
        [RecipeIngredient::create(Ulid::generate(), 1.0, MeasurementUnit::Gram, 0)],
        [RecipeStep::create(0, 'x')],
    );

    expect($recipe->isVisibleTo($owner))->toBeTrue();
});

it('is visible to non-owners when published', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->approve(Ulid::generate());

    expect($recipe->isVisibleTo(Ulid::generate()))->toBeTrue();
});

it('is visible to non-owners when pending_review and never rejected', function () {
    $recipe = aPendingRecipeForModeration();

    expect($recipe->isVisibleTo(Ulid::generate()))->toBeTrue();
});

it('is hidden from non-owners once pending_review after a rejection', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->reject(Ulid::generate(), 'Motivo.');
    $recipe->update('Bolo', 'x', 8, 60, [RecipeIngredient::create(Ulid::generate(), 1.0, MeasurementUnit::Gram, 0)], [RecipeStep::create(0, 'x')]);
    $recipe->publish();

    expect($recipe->status())->toBe(RecipeStatus::PendingReview)
        ->and($recipe->isVisibleTo(Ulid::generate()))->toBeFalse();
});

it('is hidden from non-owners when rejected', function () {
    $recipe = aPendingRecipeForModeration();
    $recipe->reject(Ulid::generate(), 'Motivo.');

    expect($recipe->isVisibleTo(Ulid::generate()))->toBeFalse();
});
