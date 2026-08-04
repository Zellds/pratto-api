<?php

namespace App\Application\User\DTOs;

final readonly class LoginUserOutput
{
    public function __construct(
        public string $token,
    ) {}
}
