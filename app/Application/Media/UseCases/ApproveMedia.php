<?php

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Support\MediaOutputFactory;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Exceptions\MediaNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ApproveMedia
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private MediaOutputFactory $output,
    ) {}

    public function __invoke(string $mediaId, string $reviewerId): MediaOutput
    {
        $id = Ulid::fromString($mediaId);
        $media = $this->media->findById($id);

        if ($media === null) {
            throw MediaNotFoundException::forId($id);
        }

        $media->approve(Ulid::fromString($reviewerId));
        $this->media->save($media);

        return $this->output->fromDomain($media);
    }
}
