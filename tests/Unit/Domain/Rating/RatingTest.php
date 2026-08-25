<?php

use App\Domain\Rating\Rating;
use App\Domain\Rating\Score;
use App\Domain\Shared\Ulid;

it('creates a rating with the given score', function () {
    $rating = Rating::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), Score::create(4.5));

    expect($rating->score()->value())->toBe(4.5);
});

it('changes the score', function () {
    $rating = Rating::create(Ulid::generate(), Ulid::generate(), Ulid::generate(), Score::create(3.0));

    $rating->changeScore(Score::create(5.0));

    expect($rating->score()->value())->toBe(5.0);
});

it('reconstitutes with the same field order as create', function () {
    $id = Ulid::generate();
    $recipeId = Ulid::generate();
    $userId = Ulid::generate();

    $rating = Rating::reconstitute($id, $recipeId, $userId, Score::create(2.5));

    expect($rating->id()->equals($id))->toBeTrue()
        ->and($rating->recipeId()->equals($recipeId))->toBeTrue()
        ->and($rating->userId()->equals($userId))->toBeTrue();
});
