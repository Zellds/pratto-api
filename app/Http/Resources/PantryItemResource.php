<?php

// app/Http/Resources/PantryItemResource.php

namespace App\Http\Resources;

use App\Application\Pantry\DTOs\PantryItemOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property PantryItemOutput $resource */
class PantryItemResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'pantryId' => $this->resource->pantryId,
            'ingredientId' => $this->resource->ingredientId,
            'quantity' => $this->resource->quantity,
            'unit' => $this->resource->unit,
            'needsToBuy' => $this->resource->needsToBuy,
            'isFixed' => $this->resource->isFixed,
        ];
    }

    /**
     * Without this, PHP's json_encode() drops the fractional zero from whole-number
     * floats (e.g. 3.0 becomes "3"), so a decoded response reads back as an int and
     * breaks strict-typed consumers of `quantity`.
     */
    #[\Override]
    public function jsonOptions(): int
    {
        return JSON_PRESERVE_ZERO_FRACTION;
    }
}
