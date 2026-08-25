<?php

// tests/Feature/Application/Comment/ListCommentsTest.php

use App\Application\Comment\UseCases\ListComments;
use App\Application\Comment\UseCases\PostComment;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists comments for a recipe', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    app(PostComment::class)($recipe->id, anOwner()->value(), 'Um.');
    app(PostComment::class)($recipe->id, anOwner()->value(), 'Dois.');

    $comments = app(ListComments::class)($recipe->id, 1, 20);

    expect($comments)->toHaveCount(2);
});

it('throws when the recipe does not exist', function () {
    app(ListComments::class)((string) new Symfony\Component\Uid\Ulid, 1, 20);
})->throws(RecipeNotFoundException::class);
