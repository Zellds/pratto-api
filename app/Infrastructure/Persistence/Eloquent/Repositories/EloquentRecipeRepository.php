<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Enums\MeasurementUnit;
use App\Domain\Recipe\Enums\RecipeStatus;
use App\Domain\Recipe\Recipe;
use App\Domain\Recipe\RecipeIngredient;
use App\Domain\Recipe\RecipeStep;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRecipe;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRecipeIngredient;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentRecipeStep;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentRecipeRepository implements RecipeRepositoryInterface
{
    public function findById(Ulid $id): ?Recipe
    {
        $record = EloquentRecipe::query()->with(['ingredients', 'steps'])->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Recipe $recipe): void
    {
        DB::transaction(function () use ($recipe): void {
            $record = EloquentRecipe::query()->updateOrCreate(
                ['id' => $recipe->id()->value()],
                [
                    'user_id' => $recipe->ownerId()->value(),
                    'title' => $recipe->title(),
                    'description' => $recipe->description(),
                    'portions' => $recipe->portions(),
                    'prep_time_minutes' => $recipe->prepTimeMinutes(),
                    'status' => $recipe->status()->value,
                    'cover_media_id' => $recipe->coverMediaId()?->value(),
                    'rejection_reason' => $recipe->rejectionReason(),
                    'was_ever_rejected' => $recipe->wasEverRejected(),
                    'reviewed_by' => $recipe->reviewedBy()?->value(),
                    'reviewed_at' => $recipe->reviewedAt(),
                ],
            );

            $record->ingredients()->delete();
            foreach ($recipe->ingredients() as $ingredient) {
                $record->ingredients()->create([
                    'ingredient_id' => $ingredient->ingredientId()->value(),
                    'quantity' => $ingredient->quantity(),
                    'unit' => $ingredient->unit()->value,
                    'position' => $ingredient->position(),
                ]);
            }

            $record->steps()->delete();
            foreach ($recipe->steps() as $step) {
                $record->steps()->create([
                    'position' => $step->position(),
                    'instruction' => $step->instruction(),
                ]);
            }

            $this->refreshSearchVector($record);
        });
    }

    public function delete(Ulid $id): void
    {
        EloquentRecipe::query()->whereKey($id->value())->delete();
    }

    public function search(?string $term, ?Ulid $ownerId, int $page, int $perPage): array
    {
        $query = EloquentRecipe::query()->with(['ingredients', 'steps']);

        if ($ownerId !== null) {
            $query->where('user_id', $ownerId->value());
        } else {
            $query->where(function ($publicQuery) {
                $publicQuery->where('status', RecipeStatus::Published->value)
                    ->orWhere(function ($pendingQuery) {
                        $pendingQuery->where('status', RecipeStatus::PendingReview->value)
                            ->where('was_ever_rejected', false);
                    });
            });
        }

        if ($term !== null && trim($term) !== '') {
            $query->whereRaw("search_vector @@ plainto_tsquery('portuguese', ?)", [$term]);
        }

        $records = $query->orderByDesc('created_at')->forPage($page, $perPage)->get();

        return $records->map(fn (EloquentRecipe $record) => $this->toDomain($record))->all();
    }

    public function forOwners(array $ownerIds, int $page, int $perPage): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $records = EloquentRecipe::query()
            ->with(['ingredients', 'steps'])
            ->whereIn('user_id', array_map(static fn (Ulid $id) => $id->value(), $ownerIds))
            ->where(function ($publicQuery) {
                $publicQuery->where('status', RecipeStatus::Published->value)
                    ->orWhere(function ($pendingQuery) {
                        $pendingQuery->where('status', RecipeStatus::PendingReview->value)
                            ->where('was_ever_rejected', false);
                    });
            })
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get();

        return $records->map(fn (EloquentRecipe $record) => $this->toDomain($record))->all();
    }

    private function refreshSearchVector(EloquentRecipe $record): void
    {
        $ingredientNames = DB::table('recipe_ingredients')
            ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
            ->where('recipe_ingredients.recipe_id', $record->id)
            ->pluck('ingredients.name')
            ->implode(' ');

        $searchable = trim($record->title.' '.$record->description.' '.$ingredientNames);

        DB::statement("UPDATE recipes SET search_vector = to_tsvector('portuguese', ?) WHERE id = ?", [$searchable, $record->id]);
    }

    private function toDomain(EloquentRecipe $record): Recipe
    {
        $ingredients = $record->ingredients->map(fn (EloquentRecipeIngredient $line) => RecipeIngredient::create(
            Ulid::fromString($line->ingredient_id),
            (float) $line->quantity,
            MeasurementUnit::from($line->unit),
            $line->position,
        ))->all();

        $steps = $record->steps->map(fn (EloquentRecipeStep $step) => RecipeStep::create(
            $step->position,
            $step->instruction,
        ))->all();

        return Recipe::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->user_id),
            $record->title,
            $record->description,
            $record->portions,
            $record->prep_time_minutes,
            RecipeStatus::from($record->status),
            $ingredients,
            $steps,
            $record->cover_media_id !== null ? Ulid::fromString($record->cover_media_id) : null,
            $record->rejection_reason,
            (bool) $record->was_ever_rejected,
            $record->reviewed_by !== null ? Ulid::fromString($record->reviewed_by) : null,
            $record->reviewed_at !== null ? DateTimeImmutable::createFromInterface($record->reviewed_at) : null,
        );
    }
}
