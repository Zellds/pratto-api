<?php

use App\Application\Recipe\UseCases\ApproveRecipe;
use App\Application\Recipe\UseCases\RejectRecipe;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('approves a pending review recipe', function () {
    $owner = anOwner();
    $reviewer = anAdmin();
    $recipe = createAPendingReviewRecipe($owner->value());

    $output = app(ApproveRecipe::class)($recipe->id, $reviewer->value());

    expect($output->status)->toBe('published');

    $found = app(RecipeRepositoryInterface::class)->findById(Ulid::fromString($recipe->id));
    expect($found->reviewedBy()->equals($reviewer))->toBeTrue()
        ->and($found->wasEverRejected())->toBeFalse();
});

it('rejects a pending review recipe with a reason', function () {
    $owner = anOwner();
    $reviewer = anAdmin();
    $recipe = createAPendingReviewRecipe($owner->value());

    $output = app(RejectRecipe::class)($recipe->id, $reviewer->value(), 'Foto imprópria.');

    expect($output->status)->toBe('rejected');

    $found = app(RecipeRepositoryInterface::class)->findById(Ulid::fromString($recipe->id));
    expect($found->wasEverRejected())->toBeTrue()
        ->and($found->rejectionReason())->toBe('Foto imprópria.');
});

it('throws when rejecting with an empty reason', function () {
    $owner = anOwner();
    $reviewer = anAdmin();
    $recipe = createAPendingReviewRecipe($owner->value());

    app(RejectRecipe::class)($recipe->id, $reviewer->value(), '  ');
})->throws(InvalidArgumentException::class);

it('throws RecipeNotFoundException when approving a non-existent recipe', function () {
    $reviewer = anAdmin();

    app(ApproveRecipe::class)((string) Ulid::generate(), $reviewer->value());
})->throws(App\Domain\Recipe\Exceptions\RecipeNotFoundException::class);
