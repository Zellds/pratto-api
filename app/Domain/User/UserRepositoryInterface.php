<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;

interface UserRepositoryInterface
{
    public function findByUsername(Username $username): ?User;

    public function save(User $user): void;

    /**
     * Persists a brand-new user together with its password as a single
     * atomic operation, so a failure never leaves a permanently-unusable
     * account (row created, password unset) taking the username forever.
     *
     * @throws DuplicateUsernameException when the username is already taken,
     *                                    including races where two concurrent registrations bypass the
     *                                    check-then-insert lookup and collide on the DB unique constraint.
     */
    public function registerWithPassword(User $user, string $plainPassword): void;

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
