<?php

use App\Domain\Comment\Comment;
use App\Domain\Comment\Exceptions\CommentNotOwnedException;
use App\Domain\Shared\Ulid;

it('posts a comment with a trimmed body', function () {
    $comment = Comment::post(Ulid::generate(), Ulid::generate(), Ulid::generate(), '  Ficou ótimo!  ');

    expect($comment->body())->toBe('Ficou ótimo!')
        ->and($comment->editedAt())->toBeNull();
});

it('rejects an empty body', function () {
    Comment::post(Ulid::generate(), Ulid::generate(), Ulid::generate(), '   ');
})->throws(InvalidArgumentException::class);

it('rejects a body longer than 1000 characters', function () {
    Comment::post(Ulid::generate(), Ulid::generate(), Ulid::generate(), str_repeat('a', 1001));
})->throws(InvalidArgumentException::class);

it('edits the body and records editedAt', function () {
    $comment = Comment::post(Ulid::generate(), Ulid::generate(), Ulid::generate(), 'Original.');

    $comment->edit('Editado.');

    expect($comment->body())->toBe('Editado.')
        ->and($comment->editedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('does not throw when the owner asserts ownership', function () {
    $owner = Ulid::generate();
    $comment = Comment::post(Ulid::generate(), Ulid::generate(), $owner, 'Oi.');

    $comment->assertOwnedBy($owner);

    expect(true)->toBeTrue();
});

it('throws when a non-owner asserts ownership', function () {
    $comment = Comment::post(Ulid::generate(), Ulid::generate(), Ulid::generate(), 'Oi.');

    $comment->assertOwnedBy(Ulid::generate());
})->throws(CommentNotOwnedException::class);
