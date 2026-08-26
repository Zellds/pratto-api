<?php

namespace App\Domain\Pantry;

use App\Domain\Shared\Ulid;
use DateTimeImmutable;

/**
 * A single invited-member relationship: one user with access to one
 * pantry that they don't own. Has no editable state — it either exists
 * or it doesn't — so there is no update method, only create/reconstitute.
 * Never represents the owner: PantryMembershipRepositoryInterface::hasAccess()
 * checks ownership separately.
 */
final class PantryMembership
{
    private function __construct(
        private readonly Ulid $id,
        private readonly Ulid $pantryId,
        private readonly Ulid $userId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(Ulid $id, Ulid $pantryId, Ulid $userId): self
    {
        return new self($id, $pantryId, $userId, new DateTimeImmutable());
    }

    public static function reconstitute(Ulid $id, Ulid $pantryId, Ulid $userId, DateTimeImmutable $createdAt): self
    {
        return new self($id, $pantryId, $userId, $createdAt);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function pantryId(): Ulid
    {
        return $this->pantryId;
    }

    public function userId(): Ulid
    {
        return $this->userId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
