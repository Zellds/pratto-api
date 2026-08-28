<?php

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Moderation\Report;
use App\Domain\Shared\Ulid;

it('creates an open report with the given reason', function () {
    $report = Report::create(Ulid::generate(), Ulid::generate(), ReportTargetType::Recipe, Ulid::generate(), 'Foto imprópria');

    expect($report->status())->toBe(ReportStatus::Open)
        ->and($report->reason())->toBe('Foto imprópria')
        ->and($report->targetType())->toBe(ReportTargetType::Recipe)
        ->and($report->resolvedAt())->toBeNull();
});

it('rejects an empty reason', function () {
    Report::create(Ulid::generate(), Ulid::generate(), ReportTargetType::Comment, Ulid::generate(), '   ');
})->throws(InvalidArgumentException::class);

it('resolves a report as reviewed', function () {
    $admin = Ulid::generate();
    $report = Report::create(Ulid::generate(), Ulid::generate(), ReportTargetType::User, Ulid::generate(), 'Comportamento abusivo');

    $report->resolve($admin, ReportStatus::Reviewed, 'Usuário banido.');

    expect($report->status())->toBe(ReportStatus::Reviewed)
        ->and($report->resolutionNote())->toBe('Usuário banido.')
        ->and($report->resolvedBy()->equals($admin))->toBeTrue()
        ->and($report->resolvedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('resolves a report as dismissed without a note', function () {
    $report = Report::create(Ulid::generate(), Ulid::generate(), ReportTargetType::Recipe, Ulid::generate(), 'Achei estranho');

    $report->resolve(Ulid::generate(), ReportStatus::Dismissed, null);

    expect($report->status())->toBe(ReportStatus::Dismissed)
        ->and($report->resolutionNote())->toBeNull();
});

it('rejects resolving back to open', function () {
    $report = Report::create(Ulid::generate(), Ulid::generate(), ReportTargetType::Recipe, Ulid::generate(), 'Motivo');

    $report->resolve(Ulid::generate(), ReportStatus::Open, null);
})->throws(InvalidArgumentException::class);

it('reconstitutes with the same field order as create plus resolution fields', function () {
    $id = Ulid::generate();
    $reporterId = Ulid::generate();
    $targetId = Ulid::generate();
    $resolvedBy = Ulid::generate();
    $resolvedAt = new DateTimeImmutable('2026-01-01 12:00:00');
    $createdAt = new DateTimeImmutable('2025-12-31 08:00:00');

    $report = Report::reconstitute(
        $id, $reporterId, ReportTargetType::Comment, $targetId, 'Spam',
        ReportStatus::Dismissed, 'Não é spam', $resolvedBy, $resolvedAt, $createdAt,
    );

    expect($report->id()->equals($id))->toBeTrue()
        ->and($report->reporterId()->equals($reporterId))->toBeTrue()
        ->and($report->targetId()->equals($targetId))->toBeTrue()
        ->and($report->resolvedBy()->equals($resolvedBy))->toBeTrue()
        ->and($report->createdAt())->toBe($createdAt);
});
