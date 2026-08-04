<?php

namespace App\Application\User\DTOs;

final class LoginUserOutput
{
    public function __construct(
        public readonly string $token,
    ) {}
}
