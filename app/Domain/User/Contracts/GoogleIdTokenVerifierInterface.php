<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\GoogleIdentity;

interface GoogleIdTokenVerifierInterface
{
    /**
     * Verifies the given Google ID token's signature, issuer, audience and
     * expiration, returning the identity it carries.
     *
     * @throws InvalidGoogleTokenException when the token is invalid, expired,
     *                                     or issued for a different audience.
     */
    public function verify(string $idToken): GoogleIdentity;
}
