<?php

namespace App\Application\User\UseCases;

use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;

final readonly class LinkGoogleAccount
{
    public function __construct(
        private UserRepositoryInterface $users,
        private GoogleIdTokenVerifierInterface $verifier,
    ) {}

    public function __invoke(string $userId, string $idToken): void
    {
        $identity = $this->verifier->verify($idToken);
        $id = Ulid::fromString($userId);

        $existing = $this->users->findByGoogleId($identity->googleId);

        if ($existing !== null && ! $existing->id()->equals($id)) {
            throw GoogleAccountAlreadyLinkedException::forGoogleId($identity->googleId);
        }

        $this->users->linkGoogleId($id, $identity->googleId, $identity->email);
    }
}
