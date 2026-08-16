<?php

// tests/Feature/Application/Media/AdjustFocalPointTest.php

use App\Application\Media\UseCases\AdjustFocalPoint;
use App\Domain\Media\Exceptions\MediaNotOwnedException;

it('updates the focal point and re-crops the thumbnail', function () {
    $owner = anOwner();
    $photo = aPendingRecipePhoto($owner->value());

    $output = app(AdjustFocalPoint::class)($photo->id, $owner->value(), 0.1, 0.9);

    expect($output->focalX)->toBe(0.1)
        ->and($output->focalY)->toBe(0.9);
});

it('rejects a non-owner adjusting the focal point', function () {
    $owner = anOwner();
    $intruder = anOwner();
    $photo = aPendingRecipePhoto($owner->value());

    app(AdjustFocalPoint::class)($photo->id, $intruder->value(), 0.1, 0.9);
})->throws(MediaNotOwnedException::class);
