<?php

namespace App\Infrastructure\Auth;

use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Exceptions\InvalidGoogleTokenException;
use App\Domain\User\GoogleIdentity;
use Google\Client;
use GuzzleHttp\Exception\GuzzleException;
use UnexpectedValueException;

final class GoogleApiClientIdTokenVerifier implements GoogleIdTokenVerifierInterface
{
    public function verify(string $idToken): GoogleIdentity
    {
        $clientId = config('services.google.client_id');

        if (! is_string($clientId) || $clientId === '') {
            throw InvalidGoogleTokenException::create();
        }

        $client = new Client(['client_id' => $clientId]);

        try {
            $payload = $client->verifyIdToken($idToken);
        } catch (UnexpectedValueException|GuzzleException) {
            throw InvalidGoogleTokenException::create();
        }

        if ($payload === false) {
            throw InvalidGoogleTokenException::create();
        }

        if (($payload['aud'] ?? null) !== $clientId) {
            throw InvalidGoogleTokenException::create();
        }

        return new GoogleIdentity(
            (string) $payload['sub'],
            $payload['email'] ?? null,
            (string) ($payload['name'] ?? ''),
        );
    }
}
