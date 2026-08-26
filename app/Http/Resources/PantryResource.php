<?php

namespace App\Http\Resources;

use App\Application\Pantry\DTOs\PantryOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property PantryOutput $resource */
class PantryResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ownerId' => $this->resource->ownerId,
            'name' => $this->resource->name,
            'role' => $this->resource->role,
        ];
    }
}
