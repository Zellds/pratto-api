<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;
use RuntimeException;

final readonly class UpdateProfile
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function __invoke(string $username, string $bio): UserProfileOutput
    {
        $user = $this->users->findByUsername(Username::fromString($username));

        if ($user === null) {
            throw new RuntimeException("User \"{$username}\" not found.");
        }

        $user->updateBio($bio);
        $this->users->save($user);

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
        );
    }
}
