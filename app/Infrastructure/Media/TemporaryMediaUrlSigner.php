<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Contracts\MediaUrlSignerInterface;
use Illuminate\Support\Facades\Storage;

final class TemporaryMediaUrlSigner implements MediaUrlSignerInterface
{
    // Deliberately untyped: PHP 8.3 typed class constants aren't parseable by
    // deptrac-shim 1.0.2's bundled php-parser (see rector.php withSkip for
    // AddTypeToConstRector on this file).
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
