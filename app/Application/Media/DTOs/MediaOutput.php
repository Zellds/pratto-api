<?php

namespace App\Application\Media\DTOs;

final readonly class MediaOutput
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $kind,
        public string $status,
        public float $focalX,
        public float $focalY,
        public int $width,
        public int $height,
        public string $thumbnailUrl,
        public string $displayUrl,
        public ?string $rejectionReason,
    ) {}
}
