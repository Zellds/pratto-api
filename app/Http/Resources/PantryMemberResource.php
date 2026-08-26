<?php

namespace App\Http\Resources;

use App\Application\Pantry\DTOs\PantryMemberOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property PantryMemberOutput $resource */
class PantryMemberResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->resource->userId,
            'username' => $this->resource->username,
            'displayName' => $this->resource->displayName,
            'role' => $this->resource->role,
        ];
    }
}
