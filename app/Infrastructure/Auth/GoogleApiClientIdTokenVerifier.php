<?php

namespace App\Infrastructure\Auth;

use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\GoogleIdentity;
use Google\Client;
use UnexpectedValueException;

final class GoogleApiClientIdTokenVerifier implements GoogleIdTokenVerifierInterface
{
    public function verify(string $idToken): GoogleIdentity
    {
        $client = new Client(['client_id' => config('services.google.client_id')]);

        try {
            $payload = $client->verifyIdToken($idToken);
        } catch (UnexpectedValueException) {
            throw InvalidGoogleTokenException::create();
        }

        if ($payload === false) {
            throw InvalidGoogleTokenException::create();
        }

        return new GoogleIdentity(
            (string) $payload['sub'],
            $payload['email'] ?? null,
            (string) ($payload['name'] ?? ''),
        );
    }
}
