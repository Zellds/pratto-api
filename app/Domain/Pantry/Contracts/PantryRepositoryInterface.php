<?php

namespace App\Domain\Pantry\Contracts;

use App\Domain\Pantry\Pantry;
use App\Domain\Shared\Ulid;

interface PantryRepositoryInterface
{
    public function findById(Ulid $id): ?Pantry;

    public function save(Pantry $pantry): void;

    public function delete(Ulid $id): void;

    /**
     * Counts every pantry the user has access to — owned plus member-of —
     * for enforcing PantryLimits::MAX_PANTRIES_PER_USER.
     */
    public function countPantriesForUser(Ulid $userId): int;

    /**
     * @return list<Pantry>
     */
    public function pantriesForUser(Ulid $userId): array;
}
