<?php

namespace App\Domain\User;

final readonly class GoogleIdentity
{
    public function __construct(
        public string $googleId,
        public ?string $email,
        public string $name,
    ) {}
}
