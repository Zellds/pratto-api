<?php

// tests/Feature/Application/Comment/EloquentCommentRepositoryTest.php

use App\Domain\Comment\Comment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Shared\Ulid;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('round-trips a comment through the database', function () {
    $repository = app(CommentRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();

    $comment = Comment::post(Ulid::generate(), Ulid::fromString($recipe->id), $author, 'Muito bom!');
    $repository->save($comment);

    $found = $repository->findById($comment->id());

    expect($found)->not->toBeNull()
        ->and($found->body())->toBe('Muito bom!')
        ->and($found->editedAt())->toBeNull();
});

it('persists an edit, including editedAt', function () {
    $repository = app(CommentRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $author = anOwner();

    $comment = Comment::post(Ulid::generate(), Ulid::fromString($recipe->id), $author, 'Original.');
    $repository->save($comment);

    $comment->edit('Editado.');
    $repository->save($comment);

    $found = $repository->findById($comment->id());

    expect($found->body())->toBe('Editado.')
        ->and($found->editedAt())->not->toBeNull();
});

it('deletes a comment', function () {
    $repository = app(CommentRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());
    $comment = Comment::post(Ulid::generate(), Ulid::fromString($recipe->id), anOwner(), 'Apagar.');
    $repository->save($comment);

    $repository->delete($comment->id());

    expect($repository->findById($comment->id()))->toBeNull();
});

it('lists comments for a recipe, newest first, paginated', function () {
    $repository = app(CommentRepositoryInterface::class);
    $owner = anOwner();
    $recipe = createAPendingReviewRecipe($owner->value());

    $repository->save(Comment::post(Ulid::generate(), Ulid::fromString($recipe->id), anOwner(), 'Primeiro.'));
    $repository->save(Comment::post(Ulid::generate(), Ulid::fromString($recipe->id), anOwner(), 'Segundo.'));

    $comments = $repository->forRecipe(Ulid::fromString($recipe->id), 1, 20);

    expect($comments)->toHaveCount(2)
        ->and($comments[0]->body())->toBe('Segundo.');
});
