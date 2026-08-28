<?php

use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Moderation\Report;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a report and finds it back', function () {
    $repository = app(ReportRepositoryInterface::class);
    $report = Report::create(Ulid::generate(), anOwner(), ReportTargetType::Recipe, Ulid::generate(), 'Foto imprópria');

    $repository->save($report);
    $found = $repository->findById($report->id());

    expect($found)->not->toBeNull()
        ->and($found->reason())->toBe('Foto imprópria')
        ->and($found->status())->toBe(ReportStatus::Open);
});

it('persists a resolution, including the resolvedAt timestamp', function () {
    $repository = app(ReportRepositoryInterface::class);
    $admin = anOwner();
    $report = Report::create(Ulid::generate(), anOwner(), ReportTargetType::User, Ulid::generate(), 'Comportamento abusivo');
    $repository->save($report);

    $report->resolve($admin, ReportStatus::Reviewed, 'Usuário banido.');
    $repository->save($report);

    $found = $repository->findById($report->id());

    expect($found->status())->toBe(ReportStatus::Reviewed)
        ->and($found->resolutionNote())->toBe('Usuário banido.')
        ->and($found->resolvedBy()->equals($admin))->toBeTrue()
        ->and($found->resolvedAt())->not->toBeNull();
});

it('lists reports filtered by status', function () {
    $repository = app(ReportRepositoryInterface::class);
    $open = Report::create(Ulid::generate(), anOwner(), ReportTargetType::Comment, Ulid::generate(), 'Spam');
    $dismissed = Report::create(Ulid::generate(), anOwner(), ReportTargetType::Comment, Ulid::generate(), 'Outro');
    $dismissed->resolve(anOwner(), ReportStatus::Dismissed, null);
    $repository->save($open);
    $repository->save($dismissed);

    $openReports = $repository->forStatus(ReportStatus::Open);

    expect($openReports)->toHaveCount(1)
        ->and($openReports[0]->id()->equals($open->id()))->toBeTrue();
});
