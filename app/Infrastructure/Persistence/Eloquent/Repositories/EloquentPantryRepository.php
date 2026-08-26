<?php

// app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryRepository.php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Pantry\Contracts\PantryRepositoryInterface;
use App\Domain\Pantry\Pantry;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentPantry;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentPantryMember;

final class EloquentPantryRepository implements PantryRepositoryInterface
{
    public function findById(Ulid $id): ?Pantry
    {
        $record = EloquentPantry::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Pantry $pantry): void
    {
        EloquentPantry::query()->updateOrCreate(
            ['id' => $pantry->id()->value()],
            ['owner_id' => $pantry->ownerId()->value(), 'name' => $pantry->name()],
        );
    }

    public function delete(Ulid $id): void
    {
        EloquentPantry::query()->whereKey($id->value())->delete();
    }

    public function countPantriesForUser(Ulid $userId): int
    {
        return count($this->accessibleIdsFor($userId));
    }

    public function pantriesForUser(Ulid $userId): array
    {
        $records = EloquentPantry::query()
            ->whereIn('id', $this->accessibleIdsFor($userId))
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (EloquentPantry $record) => $this->toDomain($record))->all();
    }

    /**
     * @return list<string>
     */
    private function accessibleIdsFor(Ulid $userId): array
    {
        $ownedIds = EloquentPantry::query()->where('owner_id', $userId->value())->pluck('id');
        $memberPantryIds = EloquentPantryMember::query()->where('user_id', $userId->value())->pluck('pantry_id');

        return $ownedIds->merge($memberPantryIds)->unique()->values()->all();
    }

    private function toDomain(EloquentPantry $record): Pantry
    {
        return Pantry::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->owner_id),
            $record->name,
        );
    }
}
