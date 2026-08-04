<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;

interface UserRepositoryInterface
{
    public function findByUsername(Username $username): ?User;

    public function save(User $user): void;

    /**
     * Persists the given plain-text password for the user, hashing it
     * with whatever algorithm the underlying storage mechanism uses.
     */
    public function setPassword(Ulid $id, string $plainPassword): void;

    /**
     * Returns the user when the given plain-text password matches the
     * stored credentials, or null otherwise.
     */
    public function verifyCredentials(Username $username, string $plainPassword): ?User;
}
