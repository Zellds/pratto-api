<?php

use App\Domain\Recipe\InvalidRecipeStatusTransitionException;
use App\Domain\Recipe\MeasurementUnit;
use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeNotOwnedException;
use App\Domain\Recipe\RecipeStatus;
use App\Domain\Shared\Ulid;

it('is created as draft', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);

    expect($recipe->status())->toBe(RecipeStatus::Draft)
        ->and($recipe->title())->toBe('Bolo')
        ->and($recipe->portions())->toBe(8);
});

it('rejects creation without at least one ingredient', function () {
    Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [], [aStep()]);
})->throws(InvalidArgumentException::class);

it('rejects creation without at least one step', function () {
    Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], []);
})->throws(InvalidArgumentException::class);

it('resets status to draft on update, even if it was pending_review', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);
    $recipe->publish();

    $recipe->update('Bolo novo', 'Descrição nova', 8, 60, [aLine()], [aStep()]);

    expect($recipe->status())->toBe(RecipeStatus::Draft)
        ->and($recipe->title())->toBe('Bolo novo');
});

it('publishes a draft recipe to pending_review', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);

    $recipe->publish();

    expect($recipe->status())->toBe(RecipeStatus::PendingReview);
});

it('refuses to publish a recipe that is not draft', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);
    $recipe->publish();

    $recipe->publish();
})->throws(InvalidRecipeStatusTransitionException::class);

it('asserts ownership', function () {
    $owner = Ulid::generate();
    $recipe = Recipe::create(Ulid::generate(), $owner, 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);

    $recipe->assertOwnedBy($owner);
})->throwsNoExceptions();

it('rejects an action from someone who does not own the recipe', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);

    $recipe->assertOwnedBy(Ulid::generate());
})->throws(RecipeNotOwnedException::class);

it('is visible to anyone once pending_review or published', function () {
    $owner = Ulid::generate();
    $recipe = Recipe::create(Ulid::generate(), $owner, 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);
    $recipe->publish();

    expect($recipe->isVisibleTo(null))->toBeTrue()
        ->and($recipe->isVisibleTo(Ulid::generate()))->toBeTrue();
});

it('is visible only to the owner while draft', function () {
    $owner = Ulid::generate();
    $recipe = Recipe::create(Ulid::generate(), $owner, 'Bolo', 'Bolo simples', 8, 60, [aLine()], [aStep()]);

    expect($recipe->isVisibleTo(null))->toBeFalse()
        ->and($recipe->isVisibleTo(Ulid::generate()))->toBeFalse()
        ->and($recipe->isVisibleTo($owner))->toBeTrue();
});

it('scales ingredient quantities proportionally to the requested portions', function () {
    $recipe = Recipe::create(
        Ulid::generate(),
        Ulid::generate(),
        'Bolo',
        'Bolo simples',
        4,
        60,
        [RecipeIngredient::create(Ulid::generate(), 2.0, MeasurementUnit::Gram, 0)],
        [aStep()],
    );

    $scaled = $recipe->scaledIngredients(8);

    expect($scaled[0]->quantity())->toBe(4.0);
});

it('returns the original ingredients when no portions are requested', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Bolo simples', 4, 60, [aLine()], [aStep()]);

    expect($recipe->scaledIngredients(null))->toBe($recipe->ingredients());
});

it('starts with no cover media', function () {
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Descrição', 4, 30, [aLine()], [aStep()]);

    expect($recipe->coverMediaId())->toBeNull();
});

it('accepts a cover media id', function () {
    $coverId = Ulid::generate();
    $recipe = Recipe::create(Ulid::generate(), Ulid::generate(), 'Bolo', 'Descrição', 4, 30, [aLine()], [aStep()], $coverId);

    expect($recipe->coverMediaId()->equals($coverId))->toBeTrue();
});
