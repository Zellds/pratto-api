<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\LoginUserOutput;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DisplayName;
use App\Domain\User\Exceptions\UserBannedException;
use App\Domain\User\GoogleIdentity;
use App\Domain\User\User;
use App\Domain\User\Username;

final readonly class LoginWithGoogle
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AccessTokenIssuerInterface $tokens,
        private GoogleIdTokenVerifierInterface $verifier,
    ) {}

    public function __invoke(string $idToken): LoginUserOutput
    {
        $identity = $this->verifier->verify($idToken);

        $user = $this->users->findByGoogleId($identity->googleId);

        if ($user === null) {
            $user = $this->register($identity);
        }

        if ($user->isBanned()) {
            throw UserBannedException::forReason($user->banReason());
        }

        return new LoginUserOutput($this->tokens->issueFor($user->id()));
    }

    private function register(GoogleIdentity $identity): User
    {
        $username = $this->generateUsername($identity);
        $displayName = $identity->name !== '' ? $identity->name : $username->value();
        $user = User::register(Ulid::generate(), $username, DisplayName::fromString($displayName));

        $this->users->registerWithGoogle($user, $identity->googleId, $identity->email);

        return $user;
    }

    private function generateUsername(GoogleIdentity $identity): Username
    {
        $base = $identity->name !== ''
            ? $identity->name
            : ($identity->email !== null ? strtok($identity->email, '@') : 'user');

        $normalized = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', mb_strtolower((string) $base)));

        if ($normalized === null || mb_strlen($normalized) < 3) {
            $normalized = 'user';
        }

        $candidate = $normalized;
        $suffix = 1;

        while ($this->users->findByUsername(Username::fromString($candidate)) !== null) {
            $suffix++;
            $candidate = $normalized.'_'.$suffix;
        }

        return Username::fromString($candidate);
    }
}
