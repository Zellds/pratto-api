<?php

namespace App\Application\Moderation\UseCases;

use App\Application\Moderation\DTOs\ReportOutput;
use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Exceptions\ReportNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ResolveReport
{
    public function __construct(private ReportRepositoryInterface $reports) {}

    public function __invoke(string $reportId, string $adminId, string $status, ?string $note): ReportOutput
    {
        $id = Ulid::fromString($reportId);
        $report = $this->reports->findById($id);

        if ($report === null) {
            throw ReportNotFoundException::forId($id);
        }

        $report->resolve(Ulid::fromString($adminId), ReportStatus::from($status), $note);
        $this->reports->save($report);

        return ReportOutput::fromDomain($report);
    }
}
