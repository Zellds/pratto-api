<?php

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Support\MediaOutputFactory;
use App\Domain\Media\Contracts\ImageProcessorInterface;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Exceptions\MediaNotFoundException;
use App\Domain\Media\FocalPoint;
use App\Domain\Shared\Ulid;

final readonly class AdjustFocalPoint
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private ImageProcessorInterface $imageProcessor,
        private MediaOutputFactory $output,
    ) {}

    public function __invoke(string $mediaId, string $ownerId, float $focalX, float $focalY): MediaOutput
    {
        $id = Ulid::fromString($mediaId);
        $media = $this->media->findById($id);

        if ($media === null) {
            throw MediaNotFoundException::forId($id);
        }

        $media->assertOwnedBy(Ulid::fromString($ownerId));

        $focalPoint = FocalPoint::create($focalX, $focalY);
        $media->adjustFocalPoint($focalPoint);
        $this->imageProcessor->reprocessThumbnail($media->storageKey(), $focalPoint);
        $this->media->save($media);

        return $this->output->fromDomain($media);
    }
}
