<?php

namespace App\Domain\Moderation\Contracts;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Report;
use App\Domain\Shared\Ulid;

interface ReportRepositoryInterface
{
    public function findById(Ulid $id): ?Report;

    public function save(Report $report): void;

    /**
     * @return list<Report>
     */
    public function forStatus(ReportStatus $status): array;
}
