<?php

namespace App\Application\Moderation\DTOs;

use App\Domain\Moderation\Report;

final readonly class ReportOutput
{
    public function __construct(
        public string $id,
        public string $reporterId,
        public string $targetType,
        public string $targetId,
        public string $reason,
        public string $status,
        public ?string $resolutionNote,
        public ?string $resolvedBy,
        public ?string $resolvedAt,
        public string $createdAt,
    ) {}

    public static function fromDomain(Report $report): self
    {
        return new self(
            $report->id()->value(),
            $report->reporterId()->value(),
            $report->targetType()->value,
            $report->targetId()->value(),
            $report->reason(),
            $report->status()->value,
            $report->resolutionNote(),
            $report->resolvedBy()?->value(),
            $report->resolvedAt()?->format(DATE_ATOM),
            $report->createdAt()->format(DATE_ATOM),
        );
    }
}
