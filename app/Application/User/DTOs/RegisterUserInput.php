<?php

namespace App\Application\User\DTOs;

final class RegisterUserInput
{
    public function __construct(
        public readonly string $username,
        public readonly string $displayName,
        public readonly string $password,
    ) {}
}
