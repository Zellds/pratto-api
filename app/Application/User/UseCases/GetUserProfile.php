<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;

final readonly class GetUserProfile
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function __invoke(string $username): ?UserProfileOutput
    {
        $user = $this->users->findByUsername(Username::fromString($username));

        if ($user === null) {
            return null;
        }

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
        );
    }
}
