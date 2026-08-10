<?php

namespace App\Http\Resources;

use App\Application\Ingredient\DTOs\IngredientOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property IngredientOutput $resource */
class IngredientResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'status' => $this->resource->status,
        ];
    }
}
