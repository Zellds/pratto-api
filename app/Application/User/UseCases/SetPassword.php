<?php

namespace App\Application\User\UseCases;

use App\Domain\Shared\Ulid;
use App\Domain\User\Contracts\UserRepositoryInterface;

final readonly class SetPassword
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function __invoke(string $userId, string $password): void
    {
        $this->users->setPassword(Ulid::fromString($userId), $password);
    }
}
