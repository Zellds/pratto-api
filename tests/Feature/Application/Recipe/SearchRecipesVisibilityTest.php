<?php

use App\Application\Recipe\DTOs\RecipeIngredientInput;
use App\Application\Recipe\DTOs\RecipeStepInput;
use App\Application\Recipe\DTOs\UpdateRecipeInput;
use App\Application\Recipe\UseCases\PublishRecipe;
use App\Application\Recipe\UseCases\RejectRecipe;
use App\Application\Recipe\UseCases\SearchRecipes;
use App\Application\Recipe\UseCases\UpdateRecipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('never shows a recipe resubmitted after rejection to public search, even though its status is pending_review again', function () {
    $owner = anOwner();
    $reviewer = anAdmin();
    $recipe = createAPendingReviewRecipe($owner->value());

    app(RejectRecipe::class)($recipe->id, $reviewer->value(), 'Foto imprópria.');

    app(UpdateRecipe::class)(new UpdateRecipeInput(
        $recipe->id, $owner->value(), 'Bolo revisado', 'Bolo simples', 8, 60,
        [new RecipeIngredientInput(null, 'Cenoura', 1.0, 'unidade', 0)],
        [new RecipeStepInput(0, 'Misture.')],
    ));
    $resubmitted = app(PublishRecipe::class)($recipe->id, $owner->value());

    expect($resubmitted->status)->toBe('pending_review');

    $publicResults = app(SearchRecipes::class)(null, null, 1, 20);
    expect(array_map(fn ($r) => $r->id, $publicResults))->not->toContain($recipe->id);

    $ownerResults = app(SearchRecipes::class)(null, $owner->value(), 1, 20);
    expect(array_map(fn ($r) => $r->id, $ownerResults))->toContain($recipe->id);
});

it('shows a pending_review recipe that was never rejected to public search', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());

    $publicResults = app(SearchRecipes::class)(null, null, 1, 20);
    expect(array_map(fn ($r) => $r->id, $publicResults))->toContain($recipe->id);
});
