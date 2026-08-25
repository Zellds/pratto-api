<?php

namespace App\Http\Resources;

use App\Application\Recipe\DTOs\RecipeOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property RecipeOutput $resource */
class RecipeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ownerId' => $this->resource->ownerId,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'portions' => $this->resource->portions,
            'prepTimeMinutes' => $this->resource->prepTimeMinutes,
            'status' => $this->resource->status,
            'coverMediaId' => $this->resource->coverMediaId,
            'averageRating' => $this->resource->averageRating !== null
                ? round($this->resource->averageRating, 1)
                : null,
            'ratingsCount' => $this->resource->ratingsCount,
            'ingredients' => array_map(static fn ($ingredient) => [
                'ingredientId' => $ingredient->ingredientId,
                'quantity' => $ingredient->quantity,
                'unit' => $ingredient->unit,
                'position' => $ingredient->position,
            ], $this->resource->ingredients),
            'steps' => array_map(static fn ($step) => [
                'position' => $step->position,
                'instruction' => $step->instruction,
            ], $this->resource->steps),
        ];
    }
}
