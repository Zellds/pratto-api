<?php

use App\Application\Moderation\UseCases\ReportContent;
use App\Application\Moderation\UseCases\ResolveReport;
use App\Domain\Moderation\Exceptions\ReportNotFoundException;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves a report as reviewed with a note', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $admin = anAdmin();
    $recipe = createAPendingReviewRecipe($owner->value());
    $report = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Motivo.');

    $output = app(ResolveReport::class)($report->id, $admin->value(), 'reviewed', 'Receita rejeitada.');

    expect($output->status)->toBe('reviewed')
        ->and($output->resolutionNote)->toBe('Receita rejeitada.')
        ->and($output->resolvedBy)->toBe($admin->value());
});

it('resolves a report as dismissed without a note', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $report = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Motivo.');

    $output = app(ResolveReport::class)($report->id, anAdmin()->value(), 'dismissed', null);

    expect($output->status)->toBe('dismissed')
        ->and($output->resolutionNote)->toBeNull();
});

it('throws ReportNotFoundException when resolving a non-existent report', function () {
    app(ResolveReport::class)((string) Ulid::generate(), anAdmin()->value(), 'dismissed', null);
})->throws(ReportNotFoundException::class);
