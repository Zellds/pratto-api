<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;
use InvalidArgumentException;

final class User
{
    private ?string $bio = null;

    private ?Ulid $avatarMediaId = null;

    private function __construct(
        private readonly Ulid $id,
        private readonly Username $username,
        private readonly DisplayName $displayName,
    ) {}

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
        $trimmed = trim($bio);

        if (mb_strlen($trimmed) > 280) {
            throw new InvalidArgumentException('Bio cannot exceed 280 characters.');
        }

        $this->bio = $trimmed;
    }

    public function avatarMediaId(): ?Ulid
    {
        return $this->avatarMediaId;
    }

    public function updateAvatar(?Ulid $mediaId): void
    {
        $this->avatarMediaId = $mediaId;
    }
}
