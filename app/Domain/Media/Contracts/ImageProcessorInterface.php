<?php

namespace App\Domain\Media\Contracts;

use App\Domain\Media\FocalPoint;

interface ImageProcessorInterface
{
    /**
     * Validates, reencodes and stores the original plus the thumbnail/display
     * variants under $storageKey. Throws InvalidImageException when the file
     * is not decodable or exceeds the pipeline's dimension/pixel limits.
     *
     * @return array{width: int, height: int} dimensions of the display variant
     */
    public function process(string $sourcePath, string $storageKey, FocalPoint $focalPoint): array;

    /**
     * Re-crops only the thumbnail variant from the stored original, without
     * re-validating or re-uploading the source file.
     */
    public function reprocessThumbnail(string $storageKey, FocalPoint $focalPoint): void;
}
