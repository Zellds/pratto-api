<?php

namespace App\Application\User\UseCases;

use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\AccessTokenIssuerInterface;
use App\Domain\User\Contracts\UserRepositoryInterface;

final readonly class SetPassword
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AccessTokenIssuerInterface $tokens,
    ) {}

    public function __invoke(string $userId, string $password): void
    {
        $id = Ulid::fromString($userId);

        $this->users->setPassword($id, $password);
        $this->tokens->revokeAllFor($id);
    }
}
