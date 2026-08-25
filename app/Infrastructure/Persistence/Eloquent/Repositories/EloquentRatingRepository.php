<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Rating\Contracts\RatingRepositoryInterface;
use App\Domain\Rating\Rating;
use App\Domain\Rating\Score;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRating;

final class EloquentRatingRepository implements RatingRepositoryInterface
{
    public function findByRecipeAndUser(Ulid $recipeId, Ulid $userId): ?Rating
    {
        $record = EloquentRating::query()
            ->where('recipe_id', $recipeId->value())
            ->where('user_id', $userId->value())
            ->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Rating $rating): void
    {
        EloquentRating::query()->updateOrCreate(
            ['id' => $rating->id()->value()],
            [
                'recipe_id' => $rating->recipeId()->value(),
                'user_id' => $rating->userId()->value(),
                'score' => $rating->score()->value(),
            ],
        );
    }

    public function averageAndCountFor(Ulid $recipeId): array
    {
        $row = EloquentRating::query()
            ->where('recipe_id', $recipeId->value())
            ->selectRaw('avg(score) as average, count(*) as count')
            ->first();

        return [
            'average' => $row?->average !== null ? (float) $row->average : null,
            'count' => (int) ($row?->count ?? 0),
        ];
    }

    public function averagesAndCountsFor(array $recipeIds): array
    {
        if ($recipeIds === []) {
            return [];
        }

        $rows = EloquentRating::query()
            ->whereIn('recipe_id', array_map(static fn (Ulid $id) => $id->value(), $recipeIds))
            ->selectRaw('recipe_id, avg(score) as average, count(*) as count')
            ->groupBy('recipe_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->recipe_id] = [
                'average' => (float) $row->average,
                'count' => (int) $row->count,
            ];
        }

        return $result;
    }

    private function toDomain(EloquentRating $record): Rating
    {
        return Rating::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->recipe_id),
            Ulid::fromString($record->user_id),
            Score::create((float) $record->score),
        );
    }
}
