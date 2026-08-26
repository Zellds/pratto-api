<?php

// app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryMembershipRepository.php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Pantry\Contracts\PantryMembershipRepositoryInterface;
use App\Domain\Pantry\PantryMembership;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentPantry;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentPantryMember;

final class EloquentPantryMembershipRepository implements PantryMembershipRepositoryInterface
{
    public function hasAccess(Ulid $pantryId, Ulid $userId): bool
    {
        $isOwner = EloquentPantry::query()
            ->where('id', $pantryId->value())
            ->where('owner_id', $userId->value())
            ->exists();

        return $isOwner || $this->isMember($pantryId, $userId);
    }

    public function save(PantryMembership $membership): void
    {
        EloquentPantryMember::query()->create([
            'id' => $membership->id()->value(),
            'pantry_id' => $membership->pantryId()->value(),
            'user_id' => $membership->userId()->value(),
            'created_at' => $membership->createdAt(),
        ]);
    }

    public function removeMember(Ulid $pantryId, Ulid $userId): void
    {
        EloquentPantryMember::query()
            ->where('pantry_id', $pantryId->value())
            ->where('user_id', $userId->value())
            ->delete();
    }

    public function isMember(Ulid $pantryId, Ulid $userId): bool
    {
        return EloquentPantryMember::query()
            ->where('pantry_id', $pantryId->value())
            ->where('user_id', $userId->value())
            ->exists();
    }

    public function countMembers(Ulid $pantryId): int
    {
        return EloquentPantryMember::query()->where('pantry_id', $pantryId->value())->count();
    }

    public function memberIdsFor(Ulid $pantryId): array
    {
        return EloquentPantryMember::query()
            ->where('pantry_id', $pantryId->value())
            ->pluck('user_id')
            ->map(static fn (string $id) => Ulid::fromString($id))
            ->all();
    }
}
