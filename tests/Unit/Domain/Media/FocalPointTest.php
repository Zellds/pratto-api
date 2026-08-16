<?php

use App\Domain\Media\FocalPoint;

it('accepts x/y within 0.0 and 1.0', function () {
    $point = FocalPoint::create(0.25, 0.75);

    expect($point->x())->toBe(0.25)
        ->and($point->y())->toBe(0.75);
});

it('defaults to the center', function () {
    $point = FocalPoint::center();

    expect($point->x())->toBe(0.5)
        ->and($point->y())->toBe(0.5);
});

it('rejects x below 0.0', function () {
    FocalPoint::create(-0.1, 0.5);
})->throws(InvalidArgumentException::class);

it('rejects x above 1.0', function () {
    FocalPoint::create(1.1, 0.5);
})->throws(InvalidArgumentException::class);

it('rejects y outside bounds', function () {
    FocalPoint::create(0.5, 1.5);
})->throws(InvalidArgumentException::class);
