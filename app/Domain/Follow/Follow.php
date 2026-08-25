<?php

// app/Domain/Follow/Follow.php

namespace App\Domain\Follow;

use App\Domain\Follow\Exceptions\CannotFollowSelfException;
use App\Domain\Shared\Ulid;
use DateTimeImmutable;

/**
 * Aggregate root for a single directed follow relationship: one user
 * following another. Has no editable state — it either exists or it
 * doesn't — so there is no update method, only create/reconstitute.
 */
final readonly class Follow
{
    private function __construct(
        private Ulid $id,
        private Ulid $followerId,
        private Ulid $followeeId,
        private DateTimeImmutable $createdAt,
    ) {}

    public static function create(Ulid $id, Ulid $followerId, Ulid $followeeId): self
    {
        if ($followerId->equals($followeeId)) {
            throw CannotFollowSelfException::forUser($followerId);
        }

        return new self($id, $followerId, $followeeId, new DateTimeImmutable);
    }

    public static function reconstitute(Ulid $id, Ulid $followerId, Ulid $followeeId, DateTimeImmutable $createdAt): self
    {
        return new self($id, $followerId, $followeeId, $createdAt);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function followerId(): Ulid
    {
        return $this->followerId;
    }

    public function followeeId(): Ulid
    {
        return $this->followeeId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
