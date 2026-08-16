<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Contracts\MediaUrlSignerInterface;
use Illuminate\Support\Facades\Storage;

final class TemporaryMediaUrlSigner implements MediaUrlSignerInterface
{
    private const TTL_MINUTES = 15;

    public function signedUrlsFor(string $storageKey): array
    {
        $disk = Storage::disk('media');
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        return [
            'thumbnail' => $disk->temporaryUrl($storageKey.'/thumbnail.jpg', $expiresAt),
            'display' => $disk->temporaryUrl($storageKey.'/display.jpg', $expiresAt),
        ];
    }
}
