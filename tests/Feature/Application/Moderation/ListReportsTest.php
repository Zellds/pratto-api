<?php

use App\Application\Moderation\UseCases\ListReports;
use App\Application\Moderation\UseCases\ReportContent;
use App\Application\Moderation\UseCases\ResolveReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only open reports by default', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $reported = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Motivo aberto.');
    $dismissed = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Motivo resolvido.');
    app(ResolveReport::class)($dismissed->id, anAdmin()->value(), 'dismissed', null);

    $open = app(ListReports::class)('open');

    expect(array_map(fn ($r) => $r->id, $open))->toContain($reported->id)
        ->and(array_map(fn ($r) => $r->id, $open))->not->toContain($dismissed->id);
});

it('lists reports filtered by any given status', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $report = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Motivo.');
    app(ResolveReport::class)($report->id, anAdmin()->value(), 'reviewed', 'Receita rejeitada.');

    $reviewed = app(ListReports::class)('reviewed');

    expect(array_map(fn ($r) => $r->id, $reviewed))->toContain($report->id);
});
