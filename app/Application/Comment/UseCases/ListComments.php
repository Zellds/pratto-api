<?php

namespace App\Application\Comment\UseCases;

use App\Application\Comment\DTOs\CommentOutput;
use App\Domain\Comment\Comment;
use App\Domain\Comment\Contracts\CommentRepositoryInterface;
use App\Domain\Recipe\Contracts\RecipeRepositoryInterface;
use App\Domain\Recipe\Exceptions\RecipeNotFoundException;
use App\Domain\Shared\Ulid;

final readonly class ListComments
{
    public function __construct(
        private RecipeRepositoryInterface $recipes,
        private CommentRepositoryInterface $comments,
    ) {}

    /**
     * @return list<CommentOutput>
     */
    public function __invoke(string $recipeId, int $page, int $perPage): array
    {
        $recipeUlid = Ulid::fromString($recipeId);

        if ($this->recipes->findById($recipeUlid) === null) {
            throw RecipeNotFoundException::forId($recipeUlid);
        }

        return array_map(
            static fn (Comment $comment) => new CommentOutput(
                $comment->id()->value(),
                $comment->recipeId()->value(),
                $comment->userId()->value(),
                $comment->body(),
                $comment->editedAt()?->format(DATE_ATOM),
            ),
            $this->comments->forRecipe($recipeUlid, $page, $perPage),
        );
    }
}
