<?php

namespace App\Domain\Media\Contracts;

interface MediaUrlSignerInterface
{
    /**
     * @return array{thumbnail: string, display: string}
     */
    public function signedUrlsFor(string $storageKey): array;
}
