<?php

// tests/Feature/Application/Comment/EditCommentTest.php

use App\Application\Comment\UseCases\EditComment;
use App\Application\Comment\UseCases\PostComment;
use App\Domain\Comment\Exceptions\CommentNotFoundException;
use App\Domain\Comment\Exceptions\CommentNotOwnedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;

uses(RefreshDatabase::class);

it('edits a comment owned by the requester', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();
    $comment = app(PostComment::class)($recipe->id, $author->value(), 'Original.');

    $output = app(EditComment::class)($comment->id, $author->value(), 'Editado.');

    expect($output->body)->toBe('Editado.')
        ->and($output->editedAt)->not->toBeNull();
});

it('throws when the comment does not exist', function () {
    app(EditComment::class)((string) new Ulid, anOwner()->value(), 'Novo.');
})->throws(CommentNotFoundException::class);

it('throws when a non-author tries to edit', function () {
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();
    $comment = app(PostComment::class)($recipe->id, $author->value(), 'Original.');
    $intruder = anOwner();

    app(EditComment::class)($comment->id, $intruder->value(), 'Hackeado.');
})->throws(CommentNotOwnedException::class);
