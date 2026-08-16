<?php

// tests/Feature/Application/Media/ApproveMediaTest.php

use App\Application\Media\UseCases\ApproveMedia;
use App\Domain\Media\Exceptions\MediaNotFoundException;
use Symfony\Component\Uid\Ulid;

it('approves pending media', function () {
    $owner = anOwner();
    $reviewer = anOwner();
    $photo = aPendingRecipePhoto($owner->value());

    $output = app(ApproveMedia::class)($photo->id, $reviewer->value());

    expect($output->status)->toBe('approved');
});

it('throws when the media does not exist', function () {
    app(ApproveMedia::class)((string) new Ulid, anOwner()->value());
})->throws(MediaNotFoundException::class);
