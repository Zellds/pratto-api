<?php

// tests/Feature/Application/Media/RejectMediaTest.php

use App\Application\Media\UseCases\RejectMedia;

it('rejects media with a reason', function () {
    $owner = anOwner();
    $reviewer = anOwner();
    $photo = aPendingRecipePhoto($owner->value());

    $output = app(RejectMedia::class)($photo->id, $reviewer->value(), 'blurry photo');

    expect($output->status)->toBe('rejected')
        ->and($output->rejectionReason)->toBe('blurry photo');
});

it('revokes an already-approved avatar', function () {
    $owner = anOwner();
    $reviewer = anOwner();
    $avatar = anApprovedAvatar($owner->value());

    $output = app(RejectMedia::class)($avatar->id, $reviewer->value(), null);

    expect($output->status)->toBe('rejected');
});
