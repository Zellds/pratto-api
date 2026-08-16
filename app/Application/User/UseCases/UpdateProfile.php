<?php

namespace App\Application\User\UseCases;

use App\Application\User\DTOs\UserProfileOutput;
use App\Domain\Media\Contracts\MediaRepositoryInterface;
use App\Domain\Shared\Ulid;
use App\Domain\User\Exceptions\AvatarMediaNotOwnedException;
use App\Domain\User\Username;
use App\Domain\User\UserRepositoryInterface;
use RuntimeException;

final readonly class UpdateProfile
{
    public function __construct(
        private UserRepositoryInterface $users,
        private MediaRepositoryInterface $media,
    ) {}

    public function __invoke(string $username, string $bio, ?string $avatarMediaId, bool $avatarMediaIdProvided): UserProfileOutput
    {
        $user = $this->users->findByUsername(Username::fromString($username));

        if ($user === null) {
            throw new RuntimeException("User \"{$username}\" not found.");
        }

        $user->updateBio($bio);

        if ($avatarMediaIdProvided) {
            $user->updateAvatar($this->assertAvatarUsable($avatarMediaId, $user->id()));
        }

        $this->users->save($user);

        return new UserProfileOutput(
            $user->id()->value(),
            $user->username()->value(),
            $user->displayName()->value(),
            $user->bio(),
            $user->avatarMediaId()?->value(),
        );
    }

    private function assertAvatarUsable(?string $avatarMediaId, Ulid $ownerId): ?Ulid
    {
        if ($avatarMediaId === null) {
            return null;
        }

        $mediaId = Ulid::fromString($avatarMediaId);
        $record = $this->media->findById($mediaId);

        if ($record === null || ! $record->ownerId()->equals($ownerId)) {
            throw AvatarMediaNotOwnedException::forMedia($mediaId);
        }

        return $mediaId;
    }
}
