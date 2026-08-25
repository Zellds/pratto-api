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
        public int $followersCount = 0,
        public int $followingCount = 0,
    ) {}

    public function withFollowCounts(int $followersCount, int $followingCount): self
    {
        return new self(
            $this->id,
            $this->username,
            $this->displayName,
            $this->bio,
            $this->avatarMediaId,
            $followersCount,
            $followingCount,
        );
    }
}
