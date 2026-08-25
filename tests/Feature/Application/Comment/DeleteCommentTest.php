<?php

// tests/Feature/Application/Comment/DeleteCommentTest.php

use App\Application\Comment\UseCases\DeleteComment;
use App\Application\Comment\UseCases\PostComment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Comment\Exceptions\CommentNotOwnedException;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes a comment owned by the requester', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();
    $comment = app(PostComment::class)($recipe->id, $author->value(), 'Apagar.');

    app(DeleteComment::class)($comment->id, $author->value());

    expect(app(CommentRepositoryInterface::class)->findById(Ulid::fromString($comment->id)))->toBeNull();
});

it('throws when a non-author tries to delete', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();
    $comment = app(PostComment::class)($recipe->id, $author->value(), 'Não apagar.');
    $intruder = anOwner();

    app(DeleteComment::class)($comment->id, $intruder->value());
})->throws(CommentNotOwnedException::class);
