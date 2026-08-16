<?php

use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\FocalPoint;
use App\Domain\Media\Media;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('round-trips a media through the database', function () {
    $repository = app(MediaRepositoryInterface::class);
    $owner = anOwner();

    $media = Media::upload(Ulid::generate(), $owner, MediaKind::RecipePhoto, 'abc123', FocalPoint::create(0.3, 0.7), 1200, 800);
    $repository->save($media);

    $found = $repository->findById($media->id());

    expect($found)->not->toBeNull()
        ->and($found->kind())->toBe(MediaKind::RecipePhoto)
        ->and($found->storageKey())->toBe('abc123')
        ->and($found->focalPoint()->x())->toBe(0.3)
        ->and($found->width())->toBe(1200);
});

it('persists an approval', function () {
    $repository = app(MediaRepositoryInterface::class);
    $owner = anOwner();
    $reviewer = anOwner();

    $media = Media::upload(Ulid::generate(), $owner, MediaKind::Avatar, 'xyz789', FocalPoint::center(), 300, 300);
    $repository->save($media);

    $media->reject($reviewer, 'blurry');
    $repository->save($media);

    $found = $repository->findById($media->id());

    expect($found->status()->value)->toBe('rejected')
        ->and($found->rejectionReason())->toBe('blurry');
});
