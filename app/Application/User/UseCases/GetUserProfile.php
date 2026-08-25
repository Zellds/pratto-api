<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class GetUserProfile
{
    public function __construct(
        private UserRepositoryInterface $users,
        private FollowRepositoryInterface $follows,
    ) {}

    public function __invoke(string $username): ?UserProfileOutput
    {
        try {
            $usernameVo = Username::fromString($username);
        } catch (InvalidArgumentException) {
            return null;
        }

        $user = $this->users->findByUsername($usernameVo);

        if ($user === null) {
            return null;
        }

        $profile = new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
            $user->avatarMediaId()?->value(),
        );

        return $profile->withFollowCounts(
            $this->follows->countFollowers($user->id()),
            $this->follows->countFollowing($user->id()),
        );
    }
}
