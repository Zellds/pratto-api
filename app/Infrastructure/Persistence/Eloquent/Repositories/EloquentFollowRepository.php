<?php

// app/Infrastructure/Persistence/Eloquent/Repositories/EloquentFollowRepository.php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Follow\Contracts\FollowRepositoryInterface;
use App\Domain\Follow\Follow;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentFollow;

final class EloquentFollowRepository implements FollowRepositoryInterface
{
    public function exists(Ulid $followerId, Ulid $followeeId): bool
    {
        return EloquentFollow::query()
            ->where('follower_id', $followerId->value())
            ->where('followee_id', $followeeId->value())
            ->exists();
    }

    public function save(Follow $follow): void
    {
        EloquentFollow::query()->create([
            'id' => $follow->id()->value(),
            'follower_id' => $follow->followerId()->value(),
            'followee_id' => $follow->followeeId()->value(),
            'created_at' => $follow->createdAt(),
        ]);
    }

    public function delete(Ulid $followerId, Ulid $followeeId): void
    {
        EloquentFollow::query()
            ->where('follower_id', $followerId->value())
            ->where('followee_id', $followeeId->value())
            ->delete();
    }

    public function countFollowers(Ulid $userId): int
    {
        return EloquentFollow::query()->where('followee_id', $userId->value())->count();
    }

    public function countFollowing(Ulid $userId): int
    {
        return EloquentFollow::query()->where('follower_id', $userId->value())->count();
    }

    public function followeeIdsFor(Ulid $followerId): array
    {
        return EloquentFollow::query()
            ->where('follower_id', $followerId->value())
            ->pluck('followee_id')
            ->map(static fn (string $id) => Ulid::fromString($id))
            ->all();
    }
}
