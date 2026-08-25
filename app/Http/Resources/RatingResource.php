<?php

namespace App\Http\Resources;

use App\Application\Rating\DTOs\RatingOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property RatingOutput $resource */
class RatingResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'recipeId' => $this->resource->recipeId,
            'userId' => $this->resource->userId,
            'score' => $this->resource->score,
        ];
    }
}
