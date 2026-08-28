<?php

use App\Application\Moderation\UseCases\ReportContent;
use App\Domain\Moderation\Contracts\ReportRepositoryInterface;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\ReportTargetType;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports a recipe', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());

    $output = app(ReportContent::class)($reporter->value(), 'recipe', $recipe->id, 'Foto imprópria.');

    expect($output->targetType)->toBe('recipe')
        ->and($output->targetId)->toBe($recipe->id)
        ->and($output->status)->toBe('open');
});

it('reports a user', function () {
    $reporter = anOwner();
    $reported = anOwner();

    $output = app(ReportContent::class)($reporter->value(), 'user', $reported->value(), 'Comportamento abusivo.');

    expect($output->targetType)->toBe('user');
});

it('reports a comment', function () {
    $owner = anOwner();
    $reporter = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $comment = app(App\Application\Comment\UseCases\PostComment::class)($recipe->id, $reporter->value(), 'Comentário.');
    $secondReporter = anOwner();

    $output = app(ReportContent::class)($secondReporter->value(), 'comment', $comment->id, 'Spam.');

    expect($output->targetType)->toBe('comment');
});

it('throws when a user reports themselves', function () {
    $user = anOwner();

    app(ReportContent::class)($user->value(), 'user', $user->value(), 'Motivo.');
})->throws(App\Domain\Moderation\Exceptions\CannotReportSelfException::class);

it('throws when the reported recipe does not exist', function () {
    $reporter = anOwner();

    app(ReportContent::class)($reporter->value(), 'recipe', (string) Ulid::generate(), 'Motivo.');
})->throws(App\Domain\Moderation\Exceptions\ReportedTargetNotFoundException::class);
