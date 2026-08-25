<?php

namespace App\Application\Comment\UseCases;

use App\Application\Comment\DTOs\CommentOutput;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Comment\Exceptions\CommentNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class EditComment
{
    public function __construct(private CommentRepositoryInterface $comments) {}

    public function __invoke(string $commentId, string $userId, string $body): CommentOutput
    {
        $id = Ulid::fromString($commentId);
        $comment = $this->comments->findById($id);

        if ($comment === null) {
            throw CommentNotFoundException::forId($id);
        }

        $comment->assertOwnedBy(Ulid::fromString($userId));
        $comment->edit($body);
        $this->comments->save($comment);

        return new CommentOutput(
            $comment->id()->value(),
            $comment->recipeId()->value(),
            $comment->userId()->value(),
            $comment->body(),
            $comment->editedAt()?->format(DATE_ATOM),
        );
    }
}
