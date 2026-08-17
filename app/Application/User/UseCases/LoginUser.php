<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\LoginUserInput;
use App\Application\User\DTOs\LoginUserOutput;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Exceptions\InvalidCredentialsException;
use App\Domain\User\Username;
use InvalidArgumentException;

final readonly class LoginUser
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AccessTokenIssuerInterface $tokens,
    ) {}

    public function __invoke(LoginUserInput $input): LoginUserOutput
    {
        try {
            $username = Username::fromString($input->username);
        } catch (InvalidArgumentException) {
            throw InvalidCredentialsException::create();
        }

        $user = $this->users->verifyCredentials($username, $input->password);

        if ($user === null) {
            throw InvalidCredentialsException::create();
        }

        return new LoginUserOutput($this->tokens->issueFor($user->id()));
    }
}
