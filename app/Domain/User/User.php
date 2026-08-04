<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;
use InvalidArgumentException;

final class User
{
    private ?string $bio = null;

    private function __construct(
        private readonly Ulid $id,
        private readonly Username $username,
        private DisplayName $displayName,
    ) {
    }

    public static function register(Ulid $id, Username $username, DisplayName $displayName): self
    {
        return new self($id, $username, $displayName);
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function username(): Username
    {
        return $this->username;
    }

    public function displayName(): DisplayName
    {
        return $this->displayName;
    }

    public function bio(): ?string
    {
        return $this->bio;
    }

    public function updateBio(string $bio): void
    {
        if (mb_strlen($bio) > 280) {
            throw new InvalidArgumentException('Bio cannot exceed 280 characters.');
        }

        $this->bio = $bio;
    }
}
