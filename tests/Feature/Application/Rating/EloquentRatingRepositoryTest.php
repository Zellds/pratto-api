<?php

// tests/Feature/Application/Rating/EloquentRatingRepositoryTest.php

use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Rating\Rating;
use App\Domain\Rating\Score;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('round-trips a rating through the database', function () {
    $repository = app(RatingRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $rater = anOwner();

    $rating = Rating::create(Ulid::generate(), Ulid::fromString($recipe->id), $rater, Score::create(4.5));
    $repository->save($rating);

    $found = $repository->findByRecipeAndUser(Ulid::fromString($recipe->id), $rater);

    expect($found)->not->toBeNull()
        ->and($found->score()->value())->toBe(4.5);
});

it('returns null when there is no rating for that recipe/user pair', function () {
    $repository = app(RatingRepositoryInterface::class);

    $found = $repository->findByRecipeAndUser(Ulid::generate(), Ulid::generate());

    expect($found)->toBeNull();
});

it('computes the average and count for a single recipe', function () {
    $repository = app(RatingRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());

    $repository->save(Rating::create(Ulid::generate(), Ulid::fromString($recipe->id), anOwner(), Score::create(4.0)));
    $repository->save(Rating::create(Ulid::generate(), Ulid::fromString($recipe->id), anOwner(), Score::create(5.0)));

    $aggregate = $repository->averageAndCountFor(Ulid::fromString($recipe->id));

    expect($aggregate['average'])->toBe(4.5)
        ->and($aggregate['count'])->toBe(2);
});

it('returns a null average and zero count for a recipe with no ratings', function () {
    $repository = app(RatingRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());

    $aggregate = $repository->averageAndCountFor(Ulid::fromString($recipe->id));

    expect($aggregate['average'])->toBeNull()
        ->and($aggregate['count'])->toBe(0);
});

it('computes averages and counts for multiple recipes in one call', function () {
    $repository = app(RatingRepositoryInterface::class);
    $owner = anOwner();
    $recipeA = createAPendingReviewRecipe($owner->value());
    $recipeB = createAPendingReviewRecipe($owner->value());

    $repository->save(Rating::create(Ulid::generate(), Ulid::fromString($recipeA->id), anOwner(), Score::create(3.0)));
    $repository->save(Rating::create(Ulid::generate(), Ulid::fromString($recipeB->id), anOwner(), Score::create(5.0)));

    $aggregates = $repository->averagesAndCountsFor([Ulid::fromString($recipeA->id), Ulid::fromString($recipeB->id)]);

    expect($aggregates[$recipeA->id]['average'])->toBe(3.0)
        ->and($aggregates[$recipeB->id]['average'])->toBe(5.0);
});
