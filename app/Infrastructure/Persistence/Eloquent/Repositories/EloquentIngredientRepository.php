<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Ingredient\Contracts\IngredientRepositoryInterface;
use App\Domain\Ingredient\Enums\IngredientStatus;
use App\Domain\Ingredient\Ingredient;
use App\Domain\Ingredient\IngredientName;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentIngredient;
use Illuminate\Support\Facades\DB;

final class EloquentIngredientRepository implements IngredientRepositoryInterface
{
    public function findById(Ulid $id): ?Ingredient
    {
        $record = EloquentIngredient::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $records = EloquentIngredient::query()
            ->whereIn('id', array_map(static fn (Ulid $id) => $id->value(), $ids))
            ->get();

        $ingredients = [];
        foreach ($records as $record) {
            $ingredients[$record->id] = $this->toDomain($record);
        }

        return $ingredients;
    }

    public function findByNormalizedName(string $normalizedName): ?Ingredient
    {
        $record = EloquentIngredient::query()->where('normalized_name', $normalizedName)->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function search(string $term, int $limit = 10): array
    {
        $normalizedTerm = IngredientName::normalize($term);

        $rows = DB::table('ingredients')
            ->selectRaw('*, similarity(normalized_name, ?) as score', [$normalizedTerm])
            ->where(function ($query) use ($normalizedTerm) {
                $query->where('normalized_name', 'ilike', $normalizedTerm.'%')
                    ->orWhereRaw('normalized_name % ?', [$normalizedTerm]);
            })
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => Ingredient::reconstitute(
            Ulid::fromString($row->id),
            IngredientName::fromString($row->name),
            IngredientStatus::from($row->status),
        ))->all();
    }

    public function save(Ingredient $ingredient): void
    {
        EloquentIngredient::query()->updateOrCreate(
            ['id' => $ingredient->id()->value()],
            [
                'name' => $ingredient->name()->value(),
                'normalized_name' => $ingredient->name()->normalized(),
                'status' => $ingredient->status()->value,
            ],
        );
    }

    private function toDomain(EloquentIngredient $record): Ingredient
    {
        return Ingredient::reconstitute(
            Ulid::fromString($record->id),
            IngredientName::fromString($record->name),
            IngredientStatus::from($record->status),
        );
    }
}
