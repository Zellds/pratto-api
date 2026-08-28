<?php

// tests/Feature/Application/Comment/AdminDeleteCommentTest.php

use App\Application\Comment\UseCases\AdminDeleteComment;
use App\Application\Comment\UseCases\PostComment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes any comment regardless of ownership', function () {
    $ownerId = anOwner();
    $recipe = createAPendingReviewRecipe($ownerId->value());
    $authorId = anOwner();
    $comment = app(PostComment::class)($recipe->id, $authorId->value(), 'Comentário impróprio.');

    app(AdminDeleteComment::class)($comment->id);

    expect(app(CommentRepositoryInterface::class)->findById(Ulid::fromString($comment->id)))->toBeNull();
});

it('throws CommentNotFoundException when deleting a non-existent comment', function () {
    app(AdminDeleteComment::class)((string) Ulid::generate());
})->throws(App\Domain\Comment\Exceptions\CommentNotFoundException::class);
