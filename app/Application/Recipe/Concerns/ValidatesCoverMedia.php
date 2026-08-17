<?php

namespace App\Application\Recipe\Concerns;

use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Recipe\Exceptions\CoverMediaNotOwnedException;
use App\Domain\Shared\Ulid;

trait ValidatesCoverMedia
{
    private function assertCoverUsable(MediaRepositoryInterface $media, ?string $coverMediaId, Ulid $ownerId): ?Ulid
    {
        if ($coverMediaId === null) {
            return null;
        }

        $coverId = Ulid::fromString($coverMediaId);
        $record = $media->findById($coverId);

        if ($record === null || ! $record->ownerId()->equals($ownerId)) {
            throw CoverMediaNotOwnedException::forMedia($coverId);
        }

        if ($record->kind() !== MediaKind::RecipePhoto) {
            throw CoverMediaNotOwnedException::forMedia($coverId);
        }

        return $coverId;
    }
}
