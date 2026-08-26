<?php

namespace App\Domain\Pantry\Contracts;

use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;

interface PantryMembershipRepositoryInterface
{
    /**
     * True if the user is the pantry's owner OR a row exists in
     * pantry_members for this pair.
     */
    public function hasAccess(Ulid $pantryId, Ulid $userId): bool;

    public function save(PantryMembership $membership): void;

    public function removeMember(Ulid $pantryId, Ulid $userId): void;

    public function isMember(Ulid $pantryId, Ulid $userId): bool;

    public function countMembers(Ulid $pantryId): int;

    /**
     * @return list<Ulid>
     */
    public function memberIdsFor(Ulid $pantryId): array;
}
