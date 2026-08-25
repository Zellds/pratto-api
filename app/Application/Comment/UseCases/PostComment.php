<?php

namespace App\Application\Comment\UseCases;

use App\Application\Comment\DTOs\CommentOutput;
use App\Domain\Comment\Comment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class PostComment
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private CommentRepositoryInterface $comments,
    ) {}

    public function __invoke(string $recipeId, string $userId, string $body): CommentOutput
    {
        $recipeUlid = Ulid::fromString($recipeId);
        $userUlid = Ulid::fromString($userId);

        $recipe = $this->recipes->findById($recipeUlid);

        if ($recipe === null || ! $recipe->isVisibleTo($userUlid)) {
            throw RecipeNotFoundException::forId($recipeUlid);
        }

        $comment = Comment::post(Ulid::generate(), $recipeUlid, $userUlid, $body);
        $this->comments->save($comment);

        return $this->toOutput($comment);
    }

    private function toOutput(Comment $comment): CommentOutput
    {
        return new CommentOutput(
            $comment->id()->value(),
            $comment->recipeId()->value(),
            $comment->userId()->value(),
            $comment->body(),
            $comment->editedAt()?->format(DATE_ATOM),
        );
    }
}
