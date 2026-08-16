<?php

namespace App\Application\Media\Support;

use App\Application\Media\DTOs\MediaOutput;
use App\Domain\Media\Contracts\MediaUrlSignerInterface;
use App\Domain\Media\Media;

final readonly class MediaOutputFactory
{
    public function __construct(private MediaUrlSignerInterface $signer) {}

    public function fromDomain(Media $media): MediaOutput
    {
        $urls = $this->signer->signedUrlsFor($media->storageKey());

        return new MediaOutput(
            $media->id()->value(),
            $media->ownerId()->value(),
            $media->kind()->value,
            $media->status()->value,
            $media->focalPoint()->x(),
            $media->focalPoint()->y(),
            $media->width(),
            $media->height(),
            $urls['thumbnail'],
            $urls['display'],
            $media->rejectionReason(),
        );
    }
}
