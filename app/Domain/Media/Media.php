<?php

namespace App\Domain\Media;

use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Exceptions\MediaNotOwnedException;
use App\Domain\Shared\Ulid;

/**
 * Aggregate root for an uploaded image (avatar or recipe cover photo):
 * ownership, storage location, focal point and moderation status.
 *
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class Media
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $ownerId,
        private readonly MediaKind $kind,
        private readonly string $storageKey,
        private FocalPoint $focalPoint,
        private readonly int $width,
        private readonly int $height,
        private MediaStatus $status,
        private ?string $rejectionReason,
        private ?Ulid $reviewedBy,
    ) {}

    /**
     * Avatars are auto-approved on upload (spec: só foto de receita passa
     * por revisão); recipe photos start pending_review.
     */
    public static function upload(
        Ulid $id,
        Ulid $ownerId,
        MediaKind $kind,
        string $storageKey,
        FocalPoint $focalPoint,
        int $width,
        int $height,
    ): self {
        $status = $kind === MediaKind::Avatar ? MediaStatus::Approved : MediaStatus::PendingReview;

        return new self($id, $ownerId, $kind, $storageKey, $focalPoint, $width, $height, $status, null, null);
    }

    public static function reconstitute(
        Ulid $id,
        Ulid $ownerId,
        MediaKind $kind,
        string $storageKey,
        FocalPoint $focalPoint,
        int $width,
        int $height,
        MediaStatus $status,
        ?string $rejectionReason,
        ?Ulid $reviewedBy,
    ): self {
        return new self($id, $ownerId, $kind, $storageKey, $focalPoint, $width, $height, $status, $rejectionReason, $reviewedBy);
    }

    public function approve(Ulid $reviewerId): void
    {
        $this->status = MediaStatus::Approved;
        $this->rejectionReason = null;
        $this->reviewedBy = $reviewerId;
    }

    /**
     * Works from any status, including approved — this is also how an
     * already-approved avatar gets revoked after the fact (spec: avatar
     * pode ficar pré-aprovado, validação posterior é responsabilidade do reject).
     */
    public function reject(Ulid $reviewerId, ?string $reason): void
    {
        $this->status = MediaStatus::Rejected;
        $this->rejectionReason = $reason;
        $this->reviewedBy = $reviewerId;
    }

    public function assertOwnedBy(Ulid $userId): void
    {
        if (! $this->ownerId->equals($userId)) {
            throw MediaNotOwnedException::forMedia($this->id);
        }
    }

    public function adjustFocalPoint(FocalPoint $focalPoint): void
    {
        $this->focalPoint = $focalPoint;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function ownerId(): Ulid
    {
        return $this->ownerId;
    }

    public function kind(): MediaKind
    {
        return $this->kind;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function focalPoint(): FocalPoint
    {
        return $this->focalPoint;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function status(): MediaStatus
    {
        return $this->status;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function reviewedBy(): ?Ulid
    {
        return $this->reviewedBy;
    }
}
