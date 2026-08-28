<?php

namespace App\Domain\User;

use App\Domain\Shared\Ulid;
use App\Domain\User\Enums\UserRole;
use App\Domain\User\Enums\UserStatus;
use DateTimeImmutable;
use InvalidArgumentException;

final class User
{
    private ?string $bio = null;

    private ?Ulid $avatarMediaId = null;

    private UserRole $role = UserRole::User;

    private UserStatus $status = UserStatus::Active;

    private ?DateTimeImmutable $bannedAt = null;

    private ?string $banReason = null;

    private ?Ulid $bannedBy = null;

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

    public function role(): UserRole
    {
        return $this->role;
    }

    public function promoteToAdmin(): void
    {
        $this->role = UserRole::Admin;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function isBanned(): bool
    {
        return $this->status === UserStatus::Banned;
    }

    public function bannedAt(): ?DateTimeImmutable
    {
        return $this->bannedAt;
    }

    public function banReason(): ?string
    {
        return $this->banReason;
    }

    public function bannedBy(): ?Ulid
    {
        return $this->bannedBy;
    }

    public function ban(Ulid $bannedBy, string $reason): void
    {
        $trimmed = trim($reason);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Ban reason cannot be empty.');
        }

        $this->status = UserStatus::Banned;
        $this->bannedAt = new DateTimeImmutable;
        $this->banReason = $trimmed;
        $this->bannedBy = $bannedBy;
    }

    public function unban(): void
    {
        $this->status = UserStatus::Active;
        $this->bannedAt = null;
        $this->banReason = null;
        $this->bannedBy = null;
    }

    /**
     * Hydration only — reconstitutes the exact moderation state persisted
     * in the database (including the original bannedAt timestamp), which
     * ban()/promoteToAdmin() cannot do since they always stamp "now" and
     * are meant for actually performing the action, not replaying history.
     * Only EloquentUserRepository should call this.
     */
    public function restoreModerationState(
        UserRole $role,
        UserStatus $status,
        ?DateTimeImmutable $bannedAt,
        ?string $banReason,
        ?Ulid $bannedBy,
    ): void {
        $this->role = $role;
        $this->status = $status;
        $this->bannedAt = $bannedAt;
        $this->banReason = $banReason;
        $this->bannedBy = $bannedBy;
    }
}
