<?php

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Support\MediaOutputFactory;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Exceptions\MediaNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class RejectMedia
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private MediaOutputFactory $output,
    ) {}

    public function __invoke(string $mediaId, string $reviewerId, ?string $reason): MediaOutput
    {
        $id = Ulid::fromString($mediaId);
        $media = $this->media->findById($id);

        if ($media === null) {
            throw MediaNotFoundException::forId($id);
        }

        $media->reject(Ulid::fromString($reviewerId), $reason);
        $this->media->save($media);

        return $this->output->fromDomain($media);
    }
}
