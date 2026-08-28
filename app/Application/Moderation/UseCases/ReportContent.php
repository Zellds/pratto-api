<?php

namespace App\Application\Moderation\UseCases;

use App\Application\Moderation\DTOs\ReportOutput;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Moderation\Exceptions\CannotReportSelfException;
use App\Domain\Moderation\Exceptions\ReportedTargetNotFoundException;
use App\Domain\Moderation\Report;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;

final readonly class ReportContent
{
    public function __construct(
        private ReportRepositoryInterface $reports,
        private RecipeRepositoryInterface $recipes,
        private UserRepositoryInterface $users,
        private CommentRepositoryInterface $comments,
    ) {}

    public function __invoke(string $reporterId, string $targetType, string $targetId, string $reason): ReportOutput
    {
        $reporter = Ulid::fromString($reporterId);
        $type = ReportTargetType::from($targetType);
        $target = Ulid::fromString($targetId);

        if ($type === ReportTargetType::User && $target->equals($reporter)) {
            throw CannotReportSelfException::forUser($reporter);
        }

        $this->assertTargetExists($type, $target);

        $report = Report::create(Ulid::generate(), $reporter, $type, $target, $reason);
        $this->reports->save($report);

        return ReportOutput::fromDomain($report);
    }

    private function assertTargetExists(ReportTargetType $type, Ulid $target): void
    {
        $exists = match ($type) {
            ReportTargetType::Recipe => $this->recipes->findById($target) !== null,
            ReportTargetType::User => $this->users->findById($target) !== null,
            ReportTargetType::Comment => $this->comments->findById($target) !== null,
        };

        if (! $exists) {
            throw ReportedTargetNotFoundException::forTarget($type, $target);
        }
    }
}
