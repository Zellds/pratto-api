<?php

namespace App\Http\Resources;

use App\Application\Media\DTOs\MediaOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property MediaOutput $resource */
class MediaResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ownerId' => $this->resource->ownerId,
            'kind' => $this->resource->kind,
            'status' => $this->resource->status,
            'focalX' => $this->resource->focalX,
            'focalY' => $this->resource->focalY,
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'thumbnailUrl' => $this->resource->thumbnailUrl,
            'displayUrl' => $this->resource->displayUrl,
            'rejectionReason' => $this->resource->rejectionReason,
        ];
    }
}
