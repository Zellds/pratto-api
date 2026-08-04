<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;

interface AccessTokenIssuerInterface
{
    /**
     * Issues a new API access token for the user and returns its plain
     * text representation.
     */
    public function issueFor(Ulid $id): string;
}
