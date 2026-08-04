<?php

namespace App\Application\User\DTOs;

final readonly class LoginUserInput
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}
}
