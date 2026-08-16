<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\FocalPoint;
use App\Domain\Media\Media;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentMedia;

final class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function findById(Ulid $id): ?Media
    {
        $record = EloquentMedia::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Media $media): void
    {
        EloquentMedia::query()->updateOrCreate(
            ['id' => $media->id()->value()],
            [
                'owner_user_id' => $media->ownerId()->value(),
                'kind' => $media->kind()->value,
                'storage_key' => $media->storageKey(),
                'focal_x' => $media->focalPoint()->x(),
                'focal_y' => $media->focalPoint()->y(),
                'width' => $media->width(),
                'height' => $media->height(),
                'status' => $media->status()->value,
                'rejection_reason' => $media->rejectionReason(),
                'reviewed_by' => $media->reviewedBy()?->value(),
                'reviewed_at' => $media->reviewedBy() !== null ? now() : null,
            ],
        );
    }

    private function toDomain(EloquentMedia $record): Media
    {
        return Media::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->owner_user_id),
            MediaKind::from($record->kind),
            $record->storage_key,
            FocalPoint::create((float) $record->focal_x, (float) $record->focal_y),
            (int) $record->width,
            (int) $record->height,
            MediaStatus::from($record->status),
            $record->rejection_reason,
            $record->reviewed_by !== null ? Ulid::fromString($record->reviewed_by) : null,
        );
    }
}
