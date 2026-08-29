<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\LoginUserOutput;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\GoogleIdTokenVerifierInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DisplayName;
use App\Domain\User\Exceptions\DuplicateUsernameException;
use App\Domain\User\Exceptions\GoogleAccountAlreadyLinkedException;
use App\Domain\User\Exceptions\UserBannedException;
use App\Domain\User\GoogleIdentity;
use App\Domain\User\User;
use App\Domain\User\Username;
use RuntimeException;

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
            $user = $this->registerOrFindExisting($identity);
        }

        if ($user->isBanned()) {
            throw UserBannedException::forReason($user->banReason());
        }

        return new LoginUserOutput($this->tokens->issueFor($user->id()));
    }

    /**
     * Registers the user, tolerating the two check-then-act races a
     * concurrent first-login-via-Google can trigger between the
     * findByGoogleId() lookup above and this registration: two simultaneous
     * requests for the same new google_id can both pass that check before
     * either commits, so the loser's registerWithGoogle() call collides on
     * either the google_id or the generated username's unique constraint.
     */
    private function registerOrFindExisting(GoogleIdentity $identity): User
    {
        try {
            return $this->register($identity);
        } catch (GoogleAccountAlreadyLinkedException) {
            // A concurrent request won the race and registered this exact
            // google_id first — the account now exists, just log in as it.
            $user = $this->users->findByGoogleId($identity->googleId);

            if ($user === null) {
                throw new RuntimeException('Race condition: google_id vanished immediately after a collision was reported.');
            }

            return $user;
        } catch (DuplicateUsernameException) {
            // A concurrent request took the generated username first — retry
            // once with a fresh username generation (which will now see the
            // just-taken name and pick a different suffix).
            return $this->register($identity);
        }
    }

    private function register(GoogleIdentity $identity): User
    {
        $username = $this->generateUsername($identity);
        $displayName = mb_substr(trim($identity->name), 0, 80);
        $displayName = $displayName !== '' ? $displayName : $username->value();
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
