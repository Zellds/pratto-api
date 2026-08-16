<?php

use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Exceptions\MediaNotOwnedException;
use App\Domain\Media\FocalPoint;
use App\Domain\Media\Media;
use App\Domain\Shared\Ulid;

it('auto-approves an avatar on upload', function () {
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::Avatar, 'abc', FocalPoint::center(), 300, 300);

    expect($media->status())->toBe(MediaStatus::Approved);
});

it('starts a recipe photo as pending review', function () {
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::RecipePhoto, 'abc', FocalPoint::center(), 1200, 900);

    expect($media->status())->toBe(MediaStatus::PendingReview);
});

it('approves pending media and records the reviewer', function () {
    $reviewer = Ulid::generate();
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::RecipePhoto, 'abc', FocalPoint::center(), 1200, 900);

    $media->approve($reviewer);

    expect($media->status())->toBe(MediaStatus::Approved)
        ->and($media->reviewedBy()->equals($reviewer))->toBeTrue();
});

it('rejects media, recording an optional reason', function () {
    $reviewer = Ulid::generate();
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::Avatar, 'abc', FocalPoint::center(), 300, 300);

    $media->reject($reviewer, 'inappropriate content');

    expect($media->status())->toBe(MediaStatus::Rejected)
        ->and($media->rejectionReason())->toBe('inappropriate content');
});

it('can revoke already-approved media back to rejected', function () {
    $reviewer = Ulid::generate();
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::Avatar, 'abc', FocalPoint::center(), 300, 300);

    expect($media->status())->toBe(MediaStatus::Approved);

    $media->reject($reviewer, 'reported after the fact');

    expect($media->status())->toBe(MediaStatus::Rejected);
});

it('clears the rejection reason when later approved', function () {
    $reviewer = Ulid::generate();
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::RecipePhoto, 'abc', FocalPoint::center(), 1200, 900);
    $media->reject($reviewer, 'blurry');

    $media->approve($reviewer);

    expect($media->rejectionReason())->toBeNull();
});

it('does not throw when the owner asserts ownership', function () {
    $owner = Ulid::generate();
    $media = Media::upload(Ulid::generate(), $owner, MediaKind::Avatar, 'abc', FocalPoint::center(), 300, 300);

    $media->assertOwnedBy($owner);

    expect(true)->toBeTrue();
});

it('throws when a non-owner asserts ownership', function () {
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::Avatar, 'abc', FocalPoint::center(), 300, 300);

    $media->assertOwnedBy(Ulid::generate());
})->throws(MediaNotOwnedException::class);

it('updates the focal point', function () {
    $media = Media::upload(Ulid::generate(), Ulid::generate(), MediaKind::RecipePhoto, 'abc', FocalPoint::center(), 1200, 900);

    $media->adjustFocalPoint(FocalPoint::create(0.2, 0.8));

    expect($media->focalPoint()->x())->toBe(0.2)
        ->and($media->focalPoint()->y())->toBe(0.8);
});
