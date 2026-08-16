<?php

namespace App\Application\Media\DTOs;

final readonly class UploadMediaInput
{
    public function __construct(
        public string $ownerId,
        public string $kind,
        public string $tmpFilePath,
        public ?float $focalX,
        public ?float $focalY,
    ) {}
}
