<?php

namespace App\Application\User\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $username,
        public string $displayName,
        public string $password,
    ) {}
}
