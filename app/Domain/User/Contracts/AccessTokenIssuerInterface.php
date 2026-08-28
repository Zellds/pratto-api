<?php

namespace App\Domain\User\Contracts;

use App\Domain\Shared\Ulid;

interface AccessTokenIssuerInterface
{
    /**
     * Issues a new API access token for the user and returns its plain
     * text representation.
     */
    public function issueFor(Ulid $id): string;

    /**
     * Revokes every existing access token for the user — called when a
     * user is banned, so an already-issued token stops working immediately
     * instead of only blocking future logins.
     */
    public function revokeAllFor(Ulid $id): void;
}
