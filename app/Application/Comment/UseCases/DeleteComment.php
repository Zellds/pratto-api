<?php

namespace App\Application\Comment\UseCases;

use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Comment\Exceptions\CommentNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class DeleteComment
{
    public function __construct(private CommentRepositoryInterface $comments) {}

    public function __invoke(string $commentId, string $userId): void
    {
        $id = Ulid::fromString($commentId);
        $comment = $this->comments->findById($id);

        if ($comment === null) {
            throw CommentNotFoundException::forId($id);
        }

        $comment->assertOwnedBy(Ulid::fromString($userId));
        $this->comments->delete($id);
    }
}
