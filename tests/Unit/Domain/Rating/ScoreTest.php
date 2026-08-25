<?php

use App\Domain\Rating\Score;

it('accepts valid half-step scores between 1.0 and 5.0', function (float $value) {
    expect(Score::create($value)->value())->toBe($value);
})->with([1.0, 1.5, 2.5, 4.0, 5.0]);

it('rejects a score below 1.0', function () {
    Score::create(0.5);
})->throws(InvalidArgumentException::class);

it('rejects a score above 5.0', function () {
    Score::create(5.5);
})->throws(InvalidArgumentException::class);

it('rejects a score that is not a half-step', function () {
    Score::create(3.2);
})->throws(InvalidArgumentException::class);
