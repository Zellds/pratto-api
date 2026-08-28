<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Moderation\Report;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentReport;
use DateTimeImmutable;

final class EloquentReportRepository implements ReportRepositoryInterface
{
    public function findById(Ulid $id): ?Report
    {
        $record = EloquentReport::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Report $report): void
    {
        EloquentReport::query()->updateOrCreate(
            ['id' => $report->id()->value()],
            [
                'reporter_id' => $report->reporterId()->value(),
                'target_type' => $report->targetType()->value,
                'target_id' => $report->targetId()->value(),
                'reason' => $report->reason(),
                'status' => $report->status()->value,
                'resolution_note' => $report->resolutionNote(),
                'resolved_by' => $report->resolvedBy()?->value(),
                'resolved_at' => $report->resolvedAt(),
            ],
        );
    }

    public function forStatus(ReportStatus $status): array
    {
        $records = EloquentReport::query()
            ->where('status', $status->value)
            ->orderBy('created_at')
            ->get();

        return $records->map(fn (EloquentReport $record) => $this->toDomain($record))->all();
    }

    private function toDomain(EloquentReport $record): Report
    {
        return Report::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->reporter_id),
            ReportTargetType::from($record->target_type),
            Ulid::fromString($record->target_id),
            $record->reason,
            ReportStatus::from($record->status),
            $record->resolution_note,
            $record->resolved_by !== null ? Ulid::fromString($record->resolved_by) : null,
            $record->resolved_at !== null ? DateTimeImmutable::createFromInterface($record->resolved_at) : null,
            DateTimeImmutable::createFromInterface($record->created_at),
        );
    }
}
