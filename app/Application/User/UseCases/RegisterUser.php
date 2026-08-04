<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\RegisterUserInput;
use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\Shared\Ulid;
use App\Domain\User\DisplayName;
use App\Domain\User\DuplicateUsernameException;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;

final readonly class RegisterUser
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function __invoke(RegisterUserInput $input): UserProfileOutput
    {
        $username = Username::fromString($input->username);

        if ($this->users->findByUsername($username) !== null) {
            throw DuplicateUsernameException::forUsername($username);
        }

        $user = User::register(Ulid::generate(), $username, DisplayName::fromString($input->displayName));

        $this->users->registerWithPassword($user, $input->password);

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
        );
    }
}
