<?php

namespace App\Domain\Comment;

use App\Domain\Comment\Exceptions\CommentNotOwnedException;
use App\Domain\Shared\Ulid;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate root for a comment on a recipe: only the author may edit or
 * delete it (enforced here via assertOwnedBy, not by the recipe's owner).
 */
final class Comment
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $recipeId,
        private readonly Ulid $userId,
        private string $body,
        private ?DateTimeImmutable $editedAt,
    ) {}

    public static function post(Ulid $id, Ulid $recipeId, Ulid $userId, string $body): self
    {
        $comment = new self($id, $recipeId, $userId, '', null);
        $comment->applyBody($body);

        return $comment;
    }

    public static function reconstitute(
        Ulid $id,
        Ulid $recipeId,
        Ulid $userId,
        string $body,
        ?DateTimeImmutable $editedAt,
    ): self {
        return new self($id, $recipeId, $userId, $body, $editedAt);
    }

    public function edit(string $body): void
    {
        $this->applyBody($body);
        $this->editedAt = new DateTimeImmutable;
    }

    public function assertOwnedBy(Ulid $userId): void
    {
        if (! $this->userId->equals($userId)) {
            throw CommentNotOwnedException::forComment($this->id);
        }
    }

    private function applyBody(string $body): void
    {
        $trimmed = trim($body);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Comment body cannot be empty.');
        }

        if (mb_strlen($trimmed) > 1000) {
            throw new InvalidArgumentException('Comment body cannot exceed 1000 characters.');
        }

        $this->body = $trimmed;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function recipeId(): Ulid
    {
        return $this->recipeId;
    }

    public function userId(): Ulid
    {
        return $this->userId;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function editedAt(): ?DateTimeImmutable
    {
        return $this->editedAt;
    }
}
