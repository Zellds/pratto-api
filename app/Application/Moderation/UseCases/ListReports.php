<?php

namespace App\Application\Moderation\UseCases;

use App\Application\Moderation\DTOs\ReportOutput;
use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportStatus;

final readonly class ListReports
{
    public function __construct(private ReportRepositoryInterface $reports) {}

    /**
     * @return list<ReportOutput>
     */
    public function __invoke(string $status = 'open'): array
    {
        $reports = $this->reports->forStatus(ReportStatus::from($status));

        return array_map(static fn ($report) => ReportOutput::fromDomain($report), $reports);
    }
}
