<?php

namespace App\Domain\Comment\Contracts;

use App\Domain\Comment\Comment;
use App\Domain\Shared\Ulid;

interface CommentRepositoryInterface
{
    public function findById(Ulid $id): ?Comment;

    public function save(Comment $comment): void;

    public function delete(Ulid $id): void;

    /**
     * @return list<Comment>
     */
    public function forRecipe(Ulid $recipeId, int $page, int $perPage): array;
}
