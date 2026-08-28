<?php

namespace App\Domain\Moderation;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Shared\Ulid;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate root for a report against a recipe, user or comment. The
 * action taken as a result (rejecting a recipe, banning a user, deleting
 * a comment) is a separate, unrelated use case — resolving a report only
 * records that an admin looked at it and what they decided.
 */
final class Report
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $reporterId,
        private readonly ReportTargetType $targetType,
        private readonly Ulid $targetId,
        private readonly string $reason,
        private ReportStatus $status,
        private ?string $resolutionNote,
        private ?Ulid $resolvedBy,
        private ?DateTimeImmutable $resolvedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(Ulid $id, Ulid $reporterId, ReportTargetType $targetType, Ulid $targetId, string $reason): self
    {
        $trimmed = trim($reason);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Report reason cannot be empty.');
        }

        return new self($id, $reporterId, $targetType, $targetId, $trimmed, ReportStatus::Open, null, null, null, new DateTimeImmutable());
    }

    public static function reconstitute(
        Ulid $id,
        Ulid $reporterId,
        ReportTargetType $targetType,
        Ulid $targetId,
        string $reason,
        ReportStatus $status,
        ?string $resolutionNote,
        ?Ulid $resolvedBy,
        ?DateTimeImmutable $resolvedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $reporterId, $targetType, $targetId, $reason, $status, $resolutionNote, $resolvedBy, $resolvedAt, $createdAt);
    }

    public function resolve(Ulid $adminId, ReportStatus $status, ?string $note): void
    {
        if ($status === ReportStatus::Open) {
            throw new InvalidArgumentException('Cannot resolve a report back to open.');
        }

        $this->status = $status;
        $this->resolutionNote = $note;
        $this->resolvedBy = $adminId;
        $this->resolvedAt = new DateTimeImmutable();
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function reporterId(): Ulid
    {
        return $this->reporterId;
    }

    public function targetType(): ReportTargetType
    {
        return $this->targetType;
    }

    public function targetId(): Ulid
    {
        return $this->targetId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function status(): ReportStatus
    {
        return $this->status;
    }

    public function resolutionNote(): ?string
    {
        return $this->resolutionNote;
    }

    public function resolvedBy(): ?Ulid
    {
        return $this->resolvedBy;
    }

    public function resolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
