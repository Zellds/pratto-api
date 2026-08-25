<?php

namespace App\Http\Resources;

use App\Application\User\DTOs\UserProfileOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property UserProfileOutput $resource */
class UserProfileResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'username' => $this->resource->username,
            'displayName' => $this->resource->displayName,
            'bio' => $this->resource->bio,
            'avatarMediaId' => $this->resource->avatarMediaId,
            'followersCount' => $this->resource->followersCount,
            'followingCount' => $this->resource->followingCount,
        ];
    }
}
