<?php

// tests/Feature/Application/Rating/RateRecipeTest.php

use App\Application\Rating\UseCases\RateRecipe;
use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a new rating', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $rater = anOwner();

    $output = app(RateRecipe::class)($recipe->id, $rater->value(), 4.0);

    expect($output->score)->toBe(4.0)
        ->and($output->recipeId)->toBe($recipe->id)
        ->and($output->userId)->toBe($rater->value());
});

it('upserts, replacing the previous score from the same user', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $rater = anOwner();

    app(RateRecipe::class)($recipe->id, $rater->value(), 2.0);
    $output = app(RateRecipe::class)($recipe->id, $rater->value(), 5.0);

    $aggregate = app(RatingRepositoryInterface::class)
        ->averageAndCountFor(Ulid::fromString($recipe->id));

    expect($output->score)->toBe(5.0)
        ->and($aggregate['count'])->toBe(1);
});

it('throws when the recipe does not exist or is not visible', function () {
    app(RateRecipe::class)((string) new Symfony\Component\Uid\Ulid, anOwner()->value(), 4.0);
})->throws(RecipeNotFoundException::class);

it('throws when the recipe is a draft owned by someone else', function () {
    $owner = anOwner();
    $draft = createADraft($owner->value());
    $intruder = anOwner();

    app(RateRecipe::class)($draft->id, $intruder->value(), 4.0);
})->throws(RecipeNotFoundException::class);
