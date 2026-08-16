<?php

namespace App\Application\User\DTOs;

final readonly class UserProfileOutput
{
    public function __construct(
        public string $id,
        public string $username,
        public string $displayName,
        public ?string $bio,
        public ?string $avatarMediaId,
    ) {}
}
