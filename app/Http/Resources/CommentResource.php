<?php

namespace App\Http\Resources;

use App\Application\Comment\DTOs\CommentOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property CommentOutput $resource */
class CommentResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'recipeId' => $this->resource->recipeId,
            'userId' => $this->resource->userId,
            'body' => $this->resource->body,
            'editedAt' => $this->resource->editedAt,
        ];
    }
}
