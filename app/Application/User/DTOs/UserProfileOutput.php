<?php

namespace App\Application\User\DTOs;

final class UserProfileOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $displayName,
        public readonly ?string $bio,
    ) {}
}
