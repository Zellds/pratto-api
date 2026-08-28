<?php

namespace App\Http\Resources;

use App\Application\Moderation\DTOs\ReportOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property ReportOutput $resource */
class ReportResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'reporterId' => $this->resource->reporterId,
            'targetType' => $this->resource->targetType,
            'targetId' => $this->resource->targetId,
            'reason' => $this->resource->reason,
            'status' => $this->resource->status,
            'resolutionNote' => $this->resource->resolutionNote,
            'resolvedBy' => $this->resource->resolvedBy,
            'resolvedAt' => $this->resource->resolvedAt,
            'createdAt' => $this->resource->createdAt,
        ];
    }
}
