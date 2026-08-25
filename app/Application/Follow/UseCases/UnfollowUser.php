<?php

// app/Application/Follow/UseCases/UnfollowUser.php

namespace App\Application\Follow\UseCases;

use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class UnfollowUser
{
    public function __construct(
        private UserRepositoryInterface $users,
        private FollowRepositoryInterface $follows,
    ) {}

    public function __invoke(string $followerId, string $followeeUsername): void
    {
        try {
            $followeeUsernameVo = Username::fromString($followeeUsername);
        } catch (InvalidArgumentException) {
            throw UserNotFoundException::forUsernameString($followeeUsername);
        }

        $followee = $this->users->findByUsername($followeeUsernameVo);

        if ($followee === null) {
            throw UserNotFoundException::forUsername($followeeUsernameVo);
        }

        $this->follows->delete(Ulid::fromString($followerId), $followee->id());
    }
}
