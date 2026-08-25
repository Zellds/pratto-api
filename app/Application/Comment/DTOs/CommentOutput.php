<?php

namespace App\Application\Comment\DTOs;

final readonly class CommentOutput
{
    public function __construct(
        public string $id,
        public string $recipeId,
        public string $userId,
        public string $body,
        public ?string $editedAt,
    ) {}
}
