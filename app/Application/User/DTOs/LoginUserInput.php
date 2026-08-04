<?php

namespace App\Application\User\DTOs;

final class LoginUserInput
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}
}
