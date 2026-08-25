<?php
// app/Domain/Follow/Contracts/FollowRepositoryInterface.php

namespace App\Domain\Follow\Contracts;

use App\Domain\Follow\Follow;
use App\Domain\Shared\Ulid;

interface FollowRepositoryInterface
{
    public function exists(Ulid $followerId, Ulid $followeeId): bool;

    public function save(Follow $follow): void;

    public function delete(Ulid $followerId, Ulid $followeeId): void;

    public function countFollowers(Ulid $userId): int;

    public function countFollowing(Ulid $userId): int;

    /**
     * @return list<Ulid>
     */
    public function followeeIdsFor(Ulid $followerId): array;
}
