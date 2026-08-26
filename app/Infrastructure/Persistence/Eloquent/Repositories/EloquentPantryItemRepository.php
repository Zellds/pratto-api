<?php

// app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPantryItemRepository.php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Pantry\Contracts\PantryItemRepositoryInterface;
use App\Domain\Pantry\PantryItem;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentPantryItem;

final class EloquentPantryItemRepository implements PantryItemRepositoryInterface
{
    public function findById(Ulid $id): ?PantryItem
    {
        $record = EloquentPantryItem::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function findByPantryAndIngredient(Ulid $pantryId, Ulid $ingredientId): ?PantryItem
    {
        $record = EloquentPantryItem::query()
            ->where('pantry_id', $pantryId->value())
            ->where('ingredient_id', $ingredientId->value())
            ->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(PantryItem $item): void
    {
        EloquentPantryItem::query()->updateOrCreate(
            ['id' => $item->id()->value()],
            [
                'pantry_id' => $item->pantryId()->value(),
                'ingredient_id' => $item->ingredientId()->value(),
                'quantity' => $item->quantity(),
                'unit' => $item->unit()->value,
                'needs_to_buy' => $item->needsToBuy(),
                'is_fixed' => $item->isFixed(),
            ],
        );
    }

    public function delete(Ulid $id): void
    {
        EloquentPantryItem::query()->whereKey($id->value())->delete();
    }

    public function forPantry(Ulid $pantryId): array
    {
        $records = EloquentPantryItem::query()
            ->where('pantry_id', $pantryId->value())
            ->orderBy('created_at')
            ->get();

        return $records->map(fn (EloquentPantryItem $record) => $this->toDomain($record))->all();
    }

    private function toDomain(EloquentPantryItem $record): PantryItem
    {
        return PantryItem::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->pantry_id),
            Ulid::fromString($record->ingredient_id),
            (float) $record->quantity,
            MeasurementUnit::from($record->unit),
            $record->needs_to_buy,
            $record->is_fixed,
        );
    }
}
