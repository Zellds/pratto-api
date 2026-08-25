<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Comment\Comment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Shared\Ulid;
use App\Infrastructure\Persistence\Eloquent\Models\EloquentComment;
use DateTimeImmutable;

final class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function findById(Ulid $id): ?Comment
    {
        $record = EloquentComment::query()->find($id->value());

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Comment $comment): void
    {
        EloquentComment::query()->updateOrCreate(
            ['id' => $comment->id()->value()],
            [
                'recipe_id' => $comment->recipeId()->value(),
                'user_id' => $comment->userId()->value(),
                'body' => $comment->body(),
                'edited_at' => $comment->editedAt(),
            ],
        );
    }

    public function delete(Ulid $id): void
    {
        EloquentComment::query()->whereKey($id->value())->delete();
    }

    public function forRecipe(Ulid $recipeId, int $page, int $perPage): array
    {
        $records = EloquentComment::query()
            ->where('recipe_id', $recipeId->value())
            ->orderByDesc('created_at')
            // Secondary tiebreaker: the comments table stores created_at with
            // 0 fractional-second precision (Task 0 migration), so two
            // comments posted within the same second would otherwise sort
            // arbitrarily. ULIDs are lexicographically time-ordered, so
            // ordering by id resolves ties in true insertion order.
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return $records->map(fn (EloquentComment $record) => $this->toDomain($record))->all();
    }

    private function toDomain(EloquentComment $record): Comment
    {
        return Comment::reconstitute(
            Ulid::fromString($record->id),
            Ulid::fromString($record->recipe_id),
            Ulid::fromString($record->user_id),
            $record->body,
            $record->edited_at !== null ? DateTimeImmutable::createFromInterface($record->edited_at) : null,
        );
    }
}
