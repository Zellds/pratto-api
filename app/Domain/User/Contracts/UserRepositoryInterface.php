<?php

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Ulid;
use App\Domain\User\Exceptions\DuplicateUsernameException;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use App\Domain\User\User;
use App\Domain\User\Username;

interface UserRepositoryInterface
{
    public function findByUsername(Username $username): ?User;

    public function findById(Ulid $id): ?User;

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

    /**
     * Returns the user linked to the given Google account, or null if no
     * account has ever logged in with it.
     */
    public function findByGoogleId(string $googleId): ?User;

    /**
     * Persists a brand-new user together with its Google identity as a
     * single atomic operation, same guarantee as registerWithPassword() —
     * a failure never leaves a permanently-unusable account taking the
     * username or the Google id forever.
     *
     * @throws DuplicateUsernameException when the username is already taken.
     * @throws GoogleAccountAlreadyLinkedException when the google id is
     *                                             already linked to another account.
     */
    public function registerWithGoogle(User $user, string $googleId, ?string $email): void;

    /**
     * Links the given Google id (and email, when Google provides one) to an
     * already-existing user.
     *
     * @throws GoogleAccountAlreadyLinkedException when the google id is
     *                                             already linked to another user, including races where a
     *                                             concurrent link bypasses the check-then-update lookup and
     *                                             collides on the DB unique constraint.
     */
    public function linkGoogleId(Ulid $id, string $googleId, ?string $email): void;
}
