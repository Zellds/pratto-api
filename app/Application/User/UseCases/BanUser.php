<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class BanUser
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AccessTokenIssuerInterface $tokens,
    ) {}

    public function __invoke(string $username, string $bannedById, string $reason): UserProfileOutput
    {
        try {
            $usernameVo = Username::fromString($username);
        } catch (InvalidArgumentException) {
            throw UserNotFoundException::forUsernameString($username);
        }

        $user = $this->users->findByUsername($usernameVo);

        if ($user === null) {
            throw UserNotFoundException::forUsernameString($username);
        }

        $user->ban(Ulid::fromString($bannedById), $reason);
        $this->users->save($user);
        $this->tokens->revokeAllFor($user->id());

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
            $user->avatarMediaId()?->value(),
        );
    }
}
