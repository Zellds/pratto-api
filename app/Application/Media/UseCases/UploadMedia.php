<?php

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\DTOs\UploadMediaInput;
use App\Application\Media\Support\MediaOutputFactory;
use App\Domain\Media\Contracts\ImageProcessorInterface;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\FocalPoint;
use App\Domain\Media\Media;
use App\Domain\Shared\Ulid;

final readonly class UploadMedia
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private ImageProcessorInterface $imageProcessor,
        private MediaOutputFactory $output,
    ) {}

    public function __invoke(UploadMediaInput $input): MediaOutput
    {
        $storageKey = Ulid::generate()->value();
        $kind = MediaKind::from($input->kind);
        $focalPoint = $input->focalX !== null && $input->focalY !== null
            ? FocalPoint::create($input->focalX, $input->focalY)
            : FocalPoint::center();

        $result = $this->imageProcessor->process($input->tmpFilePath, $storageKey, $focalPoint);

        $media = Media::upload(
            Ulid::generate(),
            Ulid::fromString($input->ownerId),
            $kind,
            $storageKey,
            $focalPoint,
            $result['width'],
            $result['height'],
        );

        $this->media->save($media);

        return $this->output->fromDomain($media);
    }
}
