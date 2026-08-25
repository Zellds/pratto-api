<?php

// tests/Feature/Application/Comment/PostCommentTest.php

use App\Application\Comment\UseCases\PostComment;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('posts a comment on a visible recipe', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();

    $output = app(PostComment::class)($recipe->id, $author->value(), 'Ótima receita!');

    expect($output->body)->toBe('Ótima receita!')
        ->and($output->recipeId)->toBe($recipe->id);
});

it('throws when the recipe does not exist or is not visible', function () {
    app(PostComment::class)((string) new Ulid, anOwner()->value(), 'Oi.');
})->throws(RecipeNotFoundException::class);
