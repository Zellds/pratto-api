<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class PromoteToAdmin
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function __invoke(string $username): UserProfileOutput
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

        $user->promoteToAdmin();
        $this->users->save($user);

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
            $user->avatarMediaId()?->value(),
        );
    }
}
